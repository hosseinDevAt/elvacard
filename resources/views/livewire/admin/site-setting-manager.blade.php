<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">تنظیمات سایت</h1>
        <button wire:click="$set('showForm', true)" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">
            + تنظیمات جدید
        </button>
    </div>

    @if($showForm)
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش تنظیمات' : 'تنظیمات جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">کلید (key)</label>
                        <input type="text" wire:model="key" placeholder="site_name" dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('key') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">گروه</label>
                        <input type="text" wire:model="group" placeholder="general" dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('group') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">نوع</label>
                        <select wire:model="type" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            <option value="string">string</option>
                            <option value="integer">integer</option>
                            <option value="boolean">boolean</option>
                            <option value="json">json</option>
                        </select>
                        @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">مقدار (value)</label>
                    @if($type === 'boolean')
                        <select wire:model="value" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            <option value="1">true</option>
                            <option value="0">false</option>
                        </select>
                    @elseif($type === 'json')
                        <textarea wire:model="value" rows="4" dir="ltr" placeholder='{"key": "value"}' class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition font-mono text-xs"></textarea>
                    @else
                        <input type="{{ $type === 'integer' ? 'number' : 'text' }}" wire:model="value" dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    @endif
                    @error('value') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در کلید، مقدار یا گروه..." class="w-full sm:w-80 px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
    </div>

    @forelse($settings as $group => $groupSettings)
        <div class="mb-6">
            <h2 class="font-bold text-gray-800 mb-3 bg-gray-100 rounded-lg px-3 py-2">{{ $group }} <span class="text-xs text-gray-500 font-normal">({{ $groupSettings->count() }})</span></h2>

            <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium text-gray-500">کلید</th>
                            <th class="px-4 py-3 text-start font-medium text-gray-500">مقدار</th>
                            <th class="px-4 py-3 text-start font-medium text-gray-500">نوع</th>
                            <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت</th>
                            <th class="px-4 py-3 text-start font-medium text-gray-500">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($groupSettings as $setting)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium" dir="ltr">{{ $setting->key }}</td>
                                <td class="px-4 py-3 text-gray-600 max-w-xs truncate" dir="ltr">{{ $setting->value }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-0.5 rounded bg-gray-100 text-gray-600 text-xs" dir="ltr">{{ $setting->type }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="{{ $setting->is_public ? 'text-green-600' : 'text-red-500' }}">{{ $setting->is_public ? 'عمومی' : 'خصوصی' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <button wire:click="edit({{ $setting->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                                    <button wire:click="delete({{ $setting->id }})" wire:confirm="آیا از حذف این تنظیمات مطمئن هستید؟" class="text-red-600 hover:text-red-800 text-xs">حذف</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">تنظیماتی یافت نشد</div>
    @endforelse
</div>