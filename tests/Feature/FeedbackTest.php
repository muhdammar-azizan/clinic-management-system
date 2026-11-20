<?php

namespace Tests\Feature;

use App\Enums\FeedbackRating;
use App\Enums\Role;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
    }

    public function test_visitors_must_sign_in_to_see_or_send_feedback(): void
    {
        $this->get('/feedback')->assertRedirect('/login');
        $this->post('/feedback', ['rating' => 3, 'area' => 'general'])->assertRedirect('/login');
        $this->get('/admin/feedback')->assertRedirect('/login');

        $this->assertSame(0, Feedback::count());
    }

    public function test_every_role_can_send_feedback(): void
    {
        foreach (Role::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/feedback')->assertOk()->assertSee('Give Feedback');
            $this->actingAs($user)->post('/feedback', ['rating' => 3, 'area' => 'general', 'comment' => 'Clear and quick'])
                ->assertRedirect('/feedback')
                ->assertSessionHas('status', 'Thank you for your feedback.');

            $this->assertDatabaseHas('feedback', ['user_id' => $user->id, 'rating' => 3, 'comment' => 'Clear and quick']);
        }
    }

    public function test_the_form_offers_the_three_ratings_and_every_area(): void
    {
        $this->actingAs(User::factory()->create())->get('/feedback')
            ->assertSee('Easy')->assertSee('Moderate')->assertSee('Hard')
            ->assertSee('Booking an appointment')->assertSee('Doctor roster')->assertSee('Medical records');
    }

    public function test_the_area_can_be_preselected_from_a_link(): void
    {
        $this->actingAs(User::factory()->create())->get('/feedback?area=roster')
            ->assertSee('<option value="roster" selected>', false);
    }

    public function test_the_comment_is_optional_and_blank_comments_are_stored_as_nothing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/feedback', ['rating' => 2, 'area' => 'booking', 'comment' => '   '])->assertSessionHasNoErrors();

        $this->assertNull(Feedback::first()->comment);
        $this->assertSame(FeedbackRating::Moderate, Feedback::first()->rating);
    }

    public function test_bad_input_is_rejected_and_nothing_is_saved(): void
    {
        $user = User::factory()->create();
        $send = fn (array $data) => $this->actingAs($user)->post('/feedback', $data);

        $send(['area' => 'general'])->assertSessionHasErrors('rating');
        $send(['rating' => 4, 'area' => 'general'])->assertSessionHasErrors('rating');
        $send(['rating' => 0, 'area' => 'general'])->assertSessionHasErrors('rating');
        $send(['rating' => 'easy', 'area' => 'general'])->assertSessionHasErrors('rating');
        $send(['rating' => 3])->assertSessionHasErrors('area');
        $send(['rating' => 3, 'area' => 'payroll'])->assertSessionHasErrors('area');
        $send(['rating' => 3, 'area' => 'general', 'comment' => str_repeat('a', 1001)])->assertSessionHasErrors('comment');

        $this->assertSame(0, Feedback::count());
    }

    public function test_a_user_cannot_send_feedback_on_behalf_of_someone_else(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($me)->post('/feedback', ['rating' => 3, 'area' => 'general', 'user_id' => $other->id]);

        $this->assertSame($me->id, Feedback::first()->user_id);
    }

    public function test_a_script_in_a_comment_is_shown_as_plain_text(): void
    {
        Feedback::factory()->create(['comment' => '<script>alert("x")</script>']);

        $this->actingAs($this->admin())->get('/admin/feedback')
            ->assertDontSee('<script>alert("x")</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_sending_too_many_in_an_hour_is_blocked(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)->post('/feedback', ['rating' => 3, 'area' => 'general'])->assertRedirect('/feedback');
        }

        $this->actingAs($user)->post('/feedback', ['rating' => 3, 'area' => 'general'])->assertStatus(429);
        $this->assertSame(10, Feedback::count());
    }

    public function test_only_admin_can_read_the_feedback_list(): void
    {
        foreach ([Role::Patient, Role::Doctor] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/admin/feedback')->assertForbidden();
        }

        $this->actingAs($this->admin())->get('/admin/feedback')->assertOk()->assertSee('No feedback has been sent yet.');
    }

    public function test_the_score_is_the_average_of_easy_3_moderate_2_and_hard_1(): void
    {
        // The report's example: three users, all Easy, gives 3.0.
        Feedback::factory()->count(3)->rated(FeedbackRating::Easy)->create();
        $all = $this->actingAs($this->admin())->get('/admin/feedback');
        $this->assertSame(3.0, $all->viewData('average'));
        $this->assertTrue($all->viewData('acceptable'));

        // Add Moderate and Hard: (3 + 3 + 3 + 2 + 1) / 5 = 2.4, which is below the 2.5 target.
        Feedback::factory()->rated(FeedbackRating::Moderate)->create();
        Feedback::factory()->rated(FeedbackRating::Hard)->create();
        $mixed = $this->actingAs($this->admin())->get('/admin/feedback');

        $this->assertSame(2.4, $mixed->viewData('average'));
        $this->assertFalse($mixed->viewData('acceptable'));
        $this->assertSame([1 => 1, 2 => 1, 3 => 3], $mixed->viewData('counts')->all());
        $this->assertSame(5, $mixed->viewData('total'));
    }

    public function test_the_threshold_is_inclusive(): void
    {
        Feedback::factory()->rated(FeedbackRating::Easy)->create();
        Feedback::factory()->rated(FeedbackRating::Moderate)->create();

        $page = $this->actingAs($this->admin())->get('/admin/feedback');

        $this->assertSame(2.5, $page->viewData('average'));
        $this->assertTrue($page->viewData('acceptable'));
    }

    public function test_filters_change_the_list_and_the_score_together(): void
    {
        $roster = Feedback::factory()->rated(FeedbackRating::Hard)->create(['area' => 'roster', 'comment' => 'Confusing calendar']);
        $booking = Feedback::factory()->rated(FeedbackRating::Easy)->create(['area' => 'booking', 'comment' => 'Smooth booking']);
        $admin = $this->admin();

        $byArea = $this->actingAs($admin)->get('/admin/feedback?area=roster');
        $byArea->assertSee('Confusing calendar')->assertDontSee('Smooth booking');
        $this->assertSame(1.0, $byArea->viewData('average'));

        $this->actingAs($admin)->get('/admin/feedback?rating=3')->assertSee('Smooth booking')->assertDontSee('Confusing calendar');
        $this->actingAs($admin)->get('/admin/feedback?search=calendar')->assertSee('Confusing calendar')->assertDontSee('Smooth booking');
        $this->actingAs($admin)->get('/admin/feedback?search='.urlencode($booking->user->email))->assertSee('Smooth booking')->assertDontSee('Confusing calendar');
        $this->actingAs($admin)->get('/admin/feedback?search=nothing-matches')->assertSee('No feedback matches your filters.');
        $this->actingAs($admin)->get('/admin/feedback?rating=9')->assertSessionHasErrors('rating');
        $this->actingAs($admin)->get('/admin/feedback?area=payroll')->assertSessionHasErrors('area');
    }

    public function test_the_by_area_table_lists_each_area_with_its_own_score(): void
    {
        Feedback::factory()->rated(FeedbackRating::Hard)->create(['area' => 'roster']);
        Feedback::factory()->rated(FeedbackRating::Easy)->create(['area' => 'roster']);
        Feedback::factory()->rated(FeedbackRating::Easy)->create(['area' => 'records']);

        $byArea = $this->actingAs($this->admin())->get('/admin/feedback')->viewData('byArea');

        $this->assertSame(['count' => 2, 'average' => 2.0], $byArea['roster']);
        $this->assertSame(['count' => 1, 'average' => 3.0], $byArea['records']);
        $this->assertFalse($byArea->has('booking'));
    }

    public function test_the_header_links_to_feedback_and_only_admin_sees_the_list(): void
    {
        $this->actingAs(User::factory()->create())->get('/patient/dashboard')
            ->assertSee(route('feedback.create'), false)
            ->assertDontSee(route('admin.feedback.index'), false);

        $this->actingAs($this->admin())->get('/admin/dashboard')
            ->assertSee(route('feedback.create'), false)
            ->assertSee(route('admin.feedback.index'), false);
    }

    public function test_deleting_a_user_removes_their_feedback(): void
    {
        $feedback = Feedback::factory()->create();
        $feedback->user->delete();

        $this->assertSame(0, Feedback::count());
    }
}
