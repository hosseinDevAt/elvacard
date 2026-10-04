<?php

namespace Tests\Unit;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SiteSettingMemoizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SiteSetting::clearRuntimeCache();
        Cache::forget('settings.public');
    }

    protected function tearDown(): void
    {
        SiteSetting::clearRuntimeCache();
        Cache::forget('settings.public');
        parent::tearDown();
    }

    public function test_multiple_calls_in_same_request_execute_only_one_cache_query(): void
    {
        SiteSetting::create([
            'key' => 'test_site_name',
            'value' => 'الواکارت تستی',
            'type' => 'string',
            'group' => 'general',
            'is_public' => true,
        ]);

        SiteSetting::clearRuntimeCache();
        Cache::forget('settings.public');

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Call site_setting 10 times in a row
        for ($i = 0; $i < 10; $i++) {
            $val = site_setting('test_site_name');
            $this->assertSame('الواکارت تستی', $val);
        }

        $queries = DB::getQueryLog();
        $cacheQueries = array_filter($queries, fn ($q) => str_contains($q['query'], '`cache`'));

        // Without memoization, this would be 10 cache queries.
        // With memoization, it must be at most 1 cache query.
        $this->assertLessThanOrEqual(1, count($cacheQueries));
    }

    public function test_missing_key_and_null_values_return_default(): void
    {
        SiteSetting::clearRuntimeCache();

        $this->assertNull(site_setting('completely_unknown_key'));
        $this->assertSame('fallback_val', site_setting('completely_unknown_key', 'fallback_val'));
        $this->assertSame(42, site_setting('completely_unknown_key', 42));

        // Create a key with null value
        SiteSetting::create([
            'key' => 'null_key',
            'value' => null,
            'type' => 'string',
            'group' => 'general',
            'is_public' => true,
        ]);

        $this->assertSame('default_for_null', site_setting('null_key', 'default_for_null'));
    }

    public function test_type_casting_works_identically(): void
    {
        SiteSetting::create([
            'key' => 'int_key',
            'value' => '12345',
            'type' => 'integer',
            'group' => 'general',
            'is_public' => true,
        ]);
        SiteSetting::create([
            'key' => 'bool_true_key',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'general',
            'is_public' => true,
        ]);
        SiteSetting::create([
            'key' => 'bool_false_key',
            'value' => '0',
            'type' => 'boolean',
            'group' => 'general',
            'is_public' => true,
        ]);
        SiteSetting::create([
            'key' => 'json_key',
            'value' => json_encode(['foo' => 'bar', 'count' => 5]),
            'type' => 'json',
            'group' => 'general',
            'is_public' => true,
        ]);

        SiteSetting::clearRuntimeCache();

        $this->assertSame(12345, site_setting('int_key'));
        $this->assertTrue(site_setting('bool_true_key'));
        $this->assertFalse(site_setting('bool_false_key'));
        $this->assertSame(['foo' => 'bar', 'count' => 5], site_setting('json_key'));
    }

    public function test_saving_setting_invalidates_runtime_and_persistent_cache(): void
    {
        $setting = SiteSetting::create([
            'key' => 'editable_key',
            'value' => 'Initial',
            'type' => 'string',
            'group' => 'general',
            'is_public' => true,
        ]);

        $this->assertSame('Initial', site_setting('editable_key'));

        // Update the setting
        $setting->update(['value' => 'Updated']);

        // The very next call must see 'Updated' immediately
        $this->assertSame('Updated', site_setting('editable_key'));
    }

    public function test_deleting_setting_invalidates_runtime_and_persistent_cache(): void
    {
        $setting = SiteSetting::create([
            'key' => 'deletable_key',
            'value' => 'WillBeDeleted',
            'type' => 'string',
            'group' => 'general',
            'is_public' => true,
        ]);

        $this->assertSame('WillBeDeleted', site_setting('deletable_key'));

        $setting->delete();

        // The very next call must return default because key was deleted
        $this->assertSame('default', site_setting('deletable_key', 'default'));
    }

    public function test_cache_forget_clears_runtime_cache(): void
    {
        SiteSetting::create([
            'key' => 'forget_key',
            'value' => 'Val1',
            'type' => 'string',
            'group' => 'general',
            'is_public' => true,
        ]);

        $this->assertSame('Val1', site_setting('forget_key'));

        // Manually update in DB bypassing Eloquent model event to test Cache::forget
        DB::table('site_settings')->where('key', 'forget_key')->update(['value' => 'Val2']);

        // Calling Cache::forget must trigger runtime cache reset
        Cache::forget('settings.public');

        $this->assertSame('Val2', site_setting('forget_key'));
    }

    public function test_runtime_cache_resets_on_application_terminating(): void
    {
        SiteSetting::create([
            'key' => 'term_key',
            'value' => 'TermVal',
            'type' => 'string',
            'group' => 'general',
            'is_public' => true,
        ]);

        site_setting('term_key');
        $this->assertNotNull(SiteSetting::getRuntimeCache());

        // Simulate request termination
        $this->app->terminate();

        $this->assertNull(SiteSetting::getRuntimeCache());
    }
}
