<?php

namespace Tests\Feature;

use App\Models\TpqSppBill;
use App\Models\TpqStudent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class TpqSppTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    private function makeStudent($masjid, array $attributes = []): TpqStudent
    {
        return TpqStudent::create([
            'masjid_id' => $masjid->id,
            'nis' => '260001',
            'name' => 'Ahmad',
            'gender' => 'L',
            'guardian_phone' => '081200001111',
            'guardian_whatsapp' => '081200001111',
            'status' => 'aktif',
            'entry_date' => now()->toDateString(),
            ...$attributes,
        ]);
    }

    public function test_generate_bills_creates_bill_for_each_active_student(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'admin');
        $this->makeStudent($masjid);

        $response = $this->actingAs($user)->post(route('admin.tpq.spp.generate'), [
            'month' => now()->month,
            'year' => now()->year,
            'amount' => 50000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tpq_spp_bills', [
            'year' => now()->year,
            'month' => now()->month,
            'amount' => 50000,
            'status' => 'unpaid',
        ]);
    }

    public function test_pay_updates_bill_status_to_paid_when_fully_paid(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'bendahara');
        $student = $this->makeStudent($masjid);

        $bill = TpqSppBill::create([
            'student_id' => $student->id,
            'year' => now()->year,
            'month' => now()->month,
            'amount' => 50000,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($user)->post(route('admin.tpq.spp.pay', $bill), [
            'amount' => 50000,
            'paid_date' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tpq_spp_bills', ['id' => $bill->id, 'status' => 'paid', 'paid_amount' => 50000]);
    }

    public function test_pay_updates_bill_status_to_partial_when_underpaid(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'bendahara');
        $student = $this->makeStudent($masjid);

        $bill = TpqSppBill::create([
            'student_id' => $student->id,
            'year' => now()->year,
            'month' => now()->month,
            'amount' => 50000,
            'status' => 'unpaid',
        ]);

        $this->actingAs($user)->post(route('admin.tpq.spp.pay', $bill), [
            'amount' => 20000,
            'paid_date' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('tpq_spp_bills', ['id' => $bill->id, 'status' => 'partial', 'paid_amount' => 20000]);
    }

    public function test_scholarship_students_bill_is_auto_paid_by_command(): void
    {
        $masjid = $this->createMasjid();
        $student = $this->makeStudent($masjid);

        TpqSppBill::create([
            'student_id' => $student->id,
            'year' => now()->subMonth()->year,
            'month' => now()->subMonth()->month,
            'amount' => 50000,
            'status' => 'paid',
            'paid_amount' => 50000,
            'is_scholarship' => true,
        ]);

        $this->travelTo(now()->startOfMonth());
        $this->artisan('tpq:generate-spp-bills')->assertSuccessful();

        $this->assertDatabaseHas('tpq_spp_bills', [
            'student_id' => $student->id,
            'year' => now()->year,
            'month' => now()->month,
            'is_scholarship' => true,
            'status' => 'paid',
        ]);
    }

    public function test_reminder_command_sends_whatsapp_for_overdue_bills_and_marks_sent(): void
    {
        config(['services.fonnte.token' => 'fake-token']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $masjid = $this->createMasjid();
        $student = $this->makeStudent($masjid);

        $bill = TpqSppBill::create([
            'student_id' => $student->id,
            'year' => now()->year,
            'month' => now()->month,
            'amount' => 50000,
            'status' => 'unpaid',
            'reminder_sent' => false,
        ]);
        $bill->forceFill(['created_at' => now()->subDays(8), 'updated_at' => now()->subDays(8)])->save();

        $this->artisan('tpq:spp-reminders')->assertSuccessful();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.fonnte.com'));
        $this->assertDatabaseHas('tpq_spp_bills', ['id' => $bill->id, 'reminder_sent' => true]);
    }

    public function test_reminder_command_skips_bills_not_yet_seven_days_old(): void
    {
        config(['services.fonnte.token' => 'fake-token']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $masjid = $this->createMasjid();
        $student = $this->makeStudent($masjid);

        $bill = TpqSppBill::create([
            'student_id' => $student->id,
            'year' => now()->year,
            'month' => now()->month,
            'amount' => 50000,
            'status' => 'unpaid',
            'reminder_sent' => false,
        ]);

        $this->artisan('tpq:spp-reminders')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertDatabaseHas('tpq_spp_bills', ['id' => $bill->id, 'reminder_sent' => false]);
    }
}
