<?php

namespace App\Support\Quality;

use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionMethod;

/**
 * Reads a PHPUnit JUnit XML log into a plain list of test results.
 * The groups (TC-001, SRS-REQ-301, ...) are not in the XML, so they are read from the test classes.
 */
class JunitParser
{
    /**
     * @param  (callable(string, string): list<string>)|null  $groupResolver  returns the groups of class, method
     * @return list<array{class: string, name: string, title: string, module: ?int, attribute: ?string, status: string, time: float, groups: list<string>}>
     */
    public function parse(string $xml, ?callable $groupResolver = null): array
    {
        $groupResolver ??= $this->groupsFromAttributes(...);

        $document = simplexml_load_string($xml);

        if ($document === false) {
            throw new \InvalidArgumentException('The JUnit log is not valid XML.');
        }

        $tests = [];

        foreach ($document->xpath('//testcase') ?: [] as $case) {
            $class = (string) ($case['class'] ?? $case['classname'] ?? '');
            $name = (string) $case['name'];

            $status = match (true) {
                isset($case->failure), isset($case->error) => 'failed',
                isset($case->skipped) => 'skipped',
                default => 'passed',
            };

            [$module, $attribute] = $this->moduleAndAttribute($class);

            $tests[] = [
                'class' => $class,
                'name' => $name,
                'title' => $this->title($name),
                'module' => $module,
                'attribute' => $attribute,
                'status' => $status,
                'time' => round((float) $case['time'], 3),
                'groups' => array_values(array_unique($groupResolver($class, $name))),
            ];
        }

        return $tests;
    }

    /**
     * "test_tc001_admin_creates_a_slot" becomes "Admin creates a slot".
     */
    public function title(string $method): string
    {
        $text = preg_replace('/^test_?/i', '', $method) ?? $method;
        $text = preg_replace('/^(tc\d+|m4_[a-z]+_?\d*)_/i', '', $text) ?? $text;

        return ucfirst(str_replace('_', ' ', $text));
    }

    /**
     * Tests\Quality\Module3\DataIntegrityTest gives [3, "Data Integrity"].
     *
     * @return array{?int, ?string}
     */
    private function moduleAndAttribute(string $class): array
    {
        if (! preg_match('/Module(\d)\\\\(\w+?)Test$/', $class, $match)) {
            return [null, null];
        }

        return [(int) $match[1], trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $match[2]) ?? $match[2])];
    }

    /**
     * @return list<string>
     */
    private function groupsFromAttributes(string $class, string $method): array
    {
        if (! class_exists($class)) {
            return [];
        }

        $groups = [];
        $sources = [new ReflectionClass($class)];

        // PHPUnit names data provider cases "method with data set", so keep only the method name.
        $method = explode(' ', $method)[0];
        $method = explode('#', $method)[0];

        if (method_exists($class, $method)) {
            $sources[] = new ReflectionMethod($class, $method);
        }

        foreach ($sources as $source) {
            foreach ($source->getAttributes(Group::class) as $attribute) {
                $groups[] = $attribute->newInstance()->name();
            }
        }

        return $groups;
    }
}
