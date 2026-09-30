<div>
    <div class="mb-6 flex items-center justify-end gap-4">
        <button wire:click="$set('showForm', true)" class="admin-btn admin-btn-primary">
            + تنظیمات جدید
        </button>
    </div>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش تنظیمات' : 'تنظیمات جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="admin-label">کلید (key)</label>
                        <input type="text" wire:model="key" placeholder="site_name" dir="ltr" class="admin-input">
                        @error('key') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">گروه</label>
                        <input type="text" wire:model="group" placeholder="general" dir="ltr" class="admin-input">
                        @error('group') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">نوع</label>
                        <select wire:model="type" class="admin-input">
                            <option value="string">string</option>
                            <option value="integer">integer</option>
                            <option value="boolean">boolean</option>
                            <option value="json">json</option>
                        </select>
                        @error('type') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="admin-label">مقدار (value)</label>
                    @if($type === 'boolean')
                        <select wire:model="value" class="admin-input">
                            <option value="1">true</option>
                            <option value="0">false</option>
                        </select>
                    @elseif($type === 'json')
                        <textarea wire:model="value" rows="4" dir="ltr" placeholder='{"key": "value"}' class="admin-input font-mono text-xs"></textarea>
                    @else
                        <input type="{{ $type === 'integer' ? 'number' : 'text' }}" wire:model="value" dir="ltr" class="admin-input">
                    @endif
                    @error('value') <p class="admin-error">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex items-center gap-2 pb-1">
                        <input type="checkbox" wire:model="isPublic" id="is_public" class="rounded border-gray-300 text-yellow-500">
                        <label for="is_public" class="text-sm text-gray-700">عمومی (قابل استفاده در قالب)</label>
                    </div>
                    <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">ذخیره</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در کلید، مقدار یا گروه..." class="admin-input sm:w-80">
    </div>

    @forelse($settings as $group => $groupSettings)
        <div class="mb-6">
            <h2 class="font-bold text-gray-800 mb-3 bg-gray-100 rounded-lg px-3 py-2">{{ $group }} <span class="text-xs text-gray-500 font-normal">({{ $groupSettings->count() }})</span></h2>

            <div class="admin-card overflow-x-auto">
        <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="admin-th">کلید</th>
                            <th class="admin-th">مقدار</th>
                            <th class="admin-th">نوع</th>
                            <th class="admin-th">وضعیت</th>
                            <th class="admin-th">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($groupSettings as $setting)
                            <tr class="hover:bg-gray-50">
                                <td class="admin-td font-medium" dir="ltr">{{ $setting->key }}</td>
                                <td class="admin-td text-gray-600 max-w-xs truncate" dir="ltr">{{ $setting->value }}</td>
                                <td class="admin-td">
                                    <span class="inline-block px-2 py-0.5 rounded bg-gray-100 text-gray-600 text-xs" dir="ltr">{{ $setting->type }}</span>
                                </td>
                                <td class="admin-td">
                                    <span class="{{ $setting->is_public ? 'text-green-600' : 'text-red-500' }}">{{ $setting->is_public ? 'عمومی' : 'خصوصی' }}</span>
                                </td>
                                <td class="admin-td">
                                    <button wire:click="edit({{ $setting->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                                    <button wire:click="delete({{ $setting->id }})" wire:confirm="آیا از حذف این تنظیمات مطمئن هستید؟" class="text-rose-600 hover:text-rose-700 text-xs font-medium transition">حذف</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="admin-card p-8 text-center text-gray-400">تنظیماتی یافت نشد</div>
    @endforelse
</div>