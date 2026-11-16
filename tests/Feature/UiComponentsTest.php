<?php

namespace Tests\Feature;

use Tests\TestCase;

class UiComponentsTest extends TestCase
{
    public function test_button_renders_as_button_or_link(): void
    {
        $this->blade('<x-button variant="danger">Remove</x-button>')
            ->assertSee('<button type="button"', false)
            ->assertSee('bg-danger', false)
            ->assertSee('Remove');

        $this->blade('<x-button href="/book" variant="accent">Book Appointment</x-button>')
            ->assertSee('<a href="/book"', false)
            ->assertSee('bg-accent', false);
    }

    public function test_badge_maps_status_to_the_right_color(): void
    {
        $this->blade('<x-badge status="done" />')->assertSee('bg-success-soft', false)->assertSee('Done');
        $this->blade('<x-badge status="booked" />')->assertSee('bg-primary-soft', false);
        $this->blade('<x-badge status="recovering" />')->assertSee('bg-warning-soft', false);
        $this->blade('<x-badge status="critical" />')->assertSee('bg-danger-soft', false);
        $this->blade('<x-badge status="cancelled" />')->assertSee('bg-neutral-soft', false);
    }

    public function test_status_badges_never_use_the_accent_color(): void
    {
        foreach (['done', 'booked', 'recovering', 'critical', 'cancelled', 'available', 'occupied', 'stable'] as $status) {
            $this->blade('<x-badge status="'.$status.'" />')->assertDontSee('accent', false);
        }
    }

    public function test_table_shows_an_empty_state_when_there_are_no_rows(): void
    {
        $this->blade('<x-table empty="No appointments found."></x-table>')
            ->assertSee('No appointments found.')
            ->assertDontSee('<table', false);
    }

    public function test_table_renders_head_and_rows(): void
    {
        $this->blade("<x-table>\n<x-slot name=\"head\"><tr><th>Doctor</th></tr></x-slot>\n<tr><td>Dr. Demo</td></tr>\n</x-table>")
            ->assertSee('<table', false)
            ->assertSee('Doctor')
            ->assertSee('Dr. Demo');
    }

    public function test_card_shows_title_and_footer(): void
    {
        $this->blade("<x-card title=\"Profile\">\n<x-slot name=\"footer\">Saved</x-slot>\nBody\n</x-card>")
            ->assertSee('Profile')
            ->assertSee('Body')
            ->assertSee('Saved');
    }

    public function test_field_shows_label_required_mark_and_hint(): void
    {
        $this->blade('<x-field label="Email" name="email" hint="We never share it." required><x-text-input id="email" name="email" /></x-field>')
            ->assertSee('Email')
            ->assertSee('*')
            ->assertSee('We never share it.')
            ->assertSee('name="email"', false);
    }

    public function test_confirm_modal_uses_delete_method_and_default_texts(): void
    {
        $this->blade('<x-confirm-modal />')
            ->assertSee('Are you sure you want to delete this record?')
            ->assertSee('This action cannot be undone.')
            ->assertSee('value="DELETE"', false)
            ->assertSee('confirm-delete.window', false);
    }

    public function test_breeze_buttons_keep_working_with_the_new_button(): void
    {
        $this->blade('<x-primary-button>Save</x-primary-button>')->assertSee('type="submit"', false)->assertSee('bg-primary', false);
        $this->blade('<x-secondary-button>Back</x-secondary-button>')->assertSee('type="button"', false);
        $this->blade('<x-danger-button>Delete</x-danger-button>')->assertSee('bg-danger', false);
    }
}
