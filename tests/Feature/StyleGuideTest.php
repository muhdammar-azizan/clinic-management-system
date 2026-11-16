<?php

namespace Tests\Feature;

use Tests\TestCase;

class StyleGuideTest extends TestCase
{
    public function test_style_guide_renders_in_local_environment(): void
    {
        $this->get('/style-guide')
            ->assertOk()
            ->assertSee('Font comparison')
            ->assertSee('Book Appointment');
    }
}
