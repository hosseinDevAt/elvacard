<?php

namespace Tests\Feature;

use App\Livewire\Admin\ManualPaymentSettingManager;
use App\Models\ManualPaymentSetting;
use App\Models\User;
use Database\Seeders\ManualPaymentSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManualPaymentSettingManagerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function createSeededSetting(): ManualPaymentSetting
    {
        $this->seed(ManualPaymentSettingSeeder::class);

        return ManualPaymentSetting::query()->findOrFail(1);
    }

    private function createActiveSetting(): ManualPaymentSetting
    {
        $setting = new ManualPaymentSetting([
            'card_number' => '6037991234567890',
            'iban' => 'IR012345678901234567890123',
            'account_name' => 'Shop Account',
            'instruction_message' => 'مبلغ را دقیقاً به این کارت واریز کنید.',
            'success_message' => 'رسید شما دریافت شد.',
            'is_active' => true,
        ]);

        $setting->id = 1;
        $setting->save();

        return $setting;
    }

    private function createOtherSetting(bool $isActive, int $id = 2): ManualPaymentSetting
    {
        $setting = new ManualPaymentSetting([
            'card_number' => '6037997000000000',
            'iban' => 'IR998877665544332211009988',
            'account_name' => 'Other Account',
            'is_active' => $isActive,
        ]);

        $setting->id = $id;
        $setting->save();

        return $setting;
    }

    // ── Access Control ──────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.manual-payment'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_is_denied_with_403(): void
    {
        $this->actingAs($this->customer())
            ->get(route('admin.manual-payment'))
            ->assertForbidden();
    }

    public function test_admin_can_access_settings_page(): void
    {
        $this->createSeededSetting();

        $this->actingAs($this->admin())
            ->get(route('admin.manual-payment'))
            ->assertOk();
    }

    public function test_customer_cannot_update_settings_page(): void
    {
        $this->createSeededSetting();

        $this->actingAs($this->customer())
            ->get(route('admin.manual-payment'))
            ->assertForbidden();
    }

    // ── Loading ─────────────────────────────────────────────────────

    public function test_seeded_inactive_singleton_loads_correctly(): void
    {
        $this->createSeededSetting();

        $component = Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class);

        $component->assertSet('loaded', true)
            ->assertSet('settingId', 1)
            ->assertSet('is_active', false)
            ->assertSet('card_number', null)
            ->assertSet('iban', null)
            ->assertSet('account_name', null);
    }

    public function test_active_setting_loads_all_fields(): void
    {
        $this->createActiveSetting();

        $component = Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class);

        $component->assertSet('loaded', true)
            ->assertSet('card_number', '6037991234567890')
            ->assertSet('iban', 'IR012345678901234567890123')
            ->assertSet('account_name', 'Shop Account')
            ->assertSet('instruction_message', 'مبلغ را دقیقاً به این کارت واریز کنید.')
            ->assertSet('success_message', 'رسید شما دریافت شد.')
            ->assertSet('is_active', true);
    }

    public function test_missing_singleton_shows_error(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class);

        $component->assertSet('loaded', false);
    }

    // ── Saving ──────────────────────────────────────────────────────

    public function test_valid_configuration_saves(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('card_number', '6037991234567890')
            ->set('iban', 'IR012345678901234567890123')
            ->set('account_name', 'Test Account')
            ->set('instruction_message', 'لطفاً مبلغ را واریز کنید.')
            ->set('success_message', 'رسید شما دریافت شد.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('manual_payment_settings', [
            'id' => 1,
            'card_number' => '6037991234567890',
            'iban' => 'IR012345678901234567890123',
            'account_name' => 'Test Account',
            'instruction_message' => 'لطفاً مبلغ را واریز کنید.',
            'success_message' => 'رسید شما دریافت شد.',
        ]);
    }

    public function test_messages_save_correctly(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('instruction_message', 'پیام جدید راهنما')
            ->set('success_message', 'پیام جدید موفقیت')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('manual_payment_settings', [
            'id' => 1,
            'instruction_message' => 'پیام جدید راهنما',
            'success_message' => 'پیام جدید موفقیت',
        ]);
    }

    public function test_boolean_activation_state_saves(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('card_number', '6037991234567890')
            ->set('iban', 'IR012345678901234567890123')
            ->set('account_name', 'Test Account')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(ManualPaymentSetting::query()->findOrFail(1)->is_active);
    }

    public function test_deactivation_saves_correctly(): void
    {
        $this->createActiveSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('is_active', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse(ManualPaymentSetting::query()->findOrFail(1)->is_active);
    }

    public function test_empty_string_fields_saved_as_null(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('card_number', '  ')
            ->set('iban', '')
            ->call('save')
            ->assertHasNoErrors();

        $setting = ManualPaymentSetting::query()->findOrFail(1);
        $this->assertNull($setting->card_number);
        $this->assertNull($setting->iban);
    }

    public function test_iban_saved_uppercase(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('iban', 'ir012345678901234567890123')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('IR012345678901234567890123', ManualPaymentSetting::query()->findOrFail(1)->iban);
    }

    // ── Validation ──────────────────────────────────────────────────

    public function test_invalid_card_number_rejected(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('card_number', '12345')
            ->call('save')
            ->assertHasErrors(['card_number']);
    }

    public function test_card_number_with_letters_rejected(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('card_number', '123456789012345a')
            ->call('save')
            ->assertHasErrors(['card_number']);
    }

    public function test_card_number_with_spaces_rejected(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('card_number', '6037 9912 3456 7890')
            ->call('save')
            ->assertHasErrors(['card_number']);
    }

    public function test_invalid_iban_rejected(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('iban', '1234567890')
            ->call('save')
            ->assertHasErrors(['iban']);
    }

    public function test_iban_with_wrong_prefix_rejected(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('iban', 'DE12345678901234567890')
            ->call('save')
            ->assertHasErrors(['iban']);
    }

    public function test_iban_with_too_few_digits_rejected(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('iban', 'IR1234')
            ->call('save')
            ->assertHasErrors(['iban']);
    }

    public function test_account_name_required_when_card_present_and_activating(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('card_number', '6037991234567890')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse(ManualPaymentSetting::query()->findOrFail(1)->is_active);
    }

    public function test_account_name_required_when_iban_present_and_activating(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('iban', 'IR012345678901234567890123')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse(ManualPaymentSetting::query()->findOrFail(1)->is_active);
    }

    public function test_draft_save_allowed_without_account_name(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('card_number', '6037991234567890')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('manual_payment_settings', [
            'id' => 1,
            'card_number' => '6037991234567890',
            'account_name' => null,
            'is_active' => false,
        ]);
    }

    // ── Activation Invariant ────────────────────────────────────────

    public function test_activation_rejected_without_card_or_iban(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('account_name', 'Test Account')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse(ManualPaymentSetting::query()->findOrFail(1)->is_active);
    }

    public function test_null_financial_fields_with_activation_rejected(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('is_active', true)
            ->set('card_number', null)
            ->set('iban', null)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse(ManualPaymentSetting::query()->findOrFail(1)->is_active);
    }

    public function test_valid_complete_configuration_can_be_activated(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('card_number', '6037991234567890')
            ->set('iban', 'IR012345678901234567890123')
            ->set('account_name', 'Test Account')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $setting = ManualPaymentSetting::query()->findOrFail(1);
        $this->assertTrue($setting->is_active);
    }

    public function test_card_only_with_account_name_can_be_activated(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('card_number', '6037991234567890')
            ->set('account_name', 'Card Only Account')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(ManualPaymentSetting::query()->findOrFail(1)->is_active);
    }

    public function test_iban_only_with_account_name_can_be_activated(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('iban', 'IR012345678901234567890123')
            ->set('account_name', 'IBAN Only Account')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(ManualPaymentSetting::query()->findOrFail(1)->is_active);
    }

    // ── Singleton Invariant ─────────────────────────────────────────

    public function test_activation_deactivates_other_rows(): void
    {
        $settingA = $this->createActiveSetting();

        $settingB = $this->createOtherSetting(true);

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('settingId', $settingA->id)
            ->set('card_number', $settingA->card_number)
            ->set('iban', $settingA->iban)
            ->set('account_name', $settingA->account_name)
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $settingA->refresh();
        $settingB->refresh();
        $this->assertTrue($settingA->is_active);
        $this->assertFalse($settingB->is_active);
    }

    public function test_only_one_active_setting_can_exist(): void
    {
        $this->createSeededSetting();

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('card_number', '6037991234567890')
            ->set('iban', 'IR012345678901234567890123')
            ->set('account_name', 'Test Account')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $activeCount = ManualPaymentSetting::query()->where('is_active', true)->count();
        $this->assertSame(1, $activeCount);
    }

    public function test_deactivation_does_not_affect_other_rows(): void
    {
        $settingA = $this->createActiveSetting();

        $settingB = $this->createOtherSetting(false);

        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('settingId', $settingA->id)
            ->set('is_active', false)
            ->call('save')
            ->assertHasNoErrors();

        $settingA->refresh();
        $settingB->refresh();
        $this->assertFalse($settingA->is_active);
        $this->assertFalse($settingB->is_active);
    }

    // ── Missing Setting Safety ──────────────────────────────────────

    public function test_save_with_missing_setting_no_update(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->set('card_number', '6037991234567890')
            ->set('iban', 'IR012345678901234567890123')
            ->set('account_name', 'Test Account')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(0, ManualPaymentSetting::query()->count());
    }

    public function test_no_duplicate_setting_created_when_missing(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ManualPaymentSettingManager::class)
            ->call('save');

        $this->assertSame(0, ManualPaymentSetting::query()->count());
    }

    // ── Security ────────────────────────────────────────────────────

    public function test_financial_values_in_admin_page_response(): void
    {
        $this->createActiveSetting();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.manual-payment'));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('6037991234567890', $content);
        $this->assertStringContainsString('IR012345678901234567890123', $content);
    }

    public function test_html_entities_escaped_in_blade(): void
    {
        $setting = new ManualPaymentSetting([
            'card_number' => '6037991234567890',
            'iban' => 'IR012345678901234567890123',
            'account_name' => '<script>alert(1)</script>',
            'is_active' => false,
        ]);

        $setting->id = 1;
        $setting->save();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.manual-payment'));

        $response->assertOk();
        $response->assertDontSee('<script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    public function test_customer_cannot_update_settings_page_directly(): void
    {
        $this->createSeededSetting();

        $this->actingAs($this->customer())
            ->get(route('admin.manual-payment'))
            ->assertForbidden();

        $setting = ManualPaymentSetting::query()->findOrFail(1);
        $this->assertNull($setting->card_number);
        $this->assertFalse($setting->is_active);
    }
}
