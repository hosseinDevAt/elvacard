@php
    $announcement = \App\Models\Announcement::visible()->first();
@endphp

@if ($announcement && $announcement->title)
    <div
        class="border-b border-gray-200 text-center py-3 px-4 sm:px-6 {{ $announcement->background_color ?? 'bg-primary-50' }} {{ $announcement->text_color ?? 'text-primary-900' }}"
        role="alert"
    >
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4 text-sm">
            <span class="font-medium">
                {{ $announcement->title }}
            </span>

            @if ($announcement->content)
                <span class="text-current opacity-75">
                    {{ $announcement->content }}
                </span>
            @endif

            @if ($announcement->link)
                <a
                    href="{{ safe_url($announcement->link) ?: '#' }}"
                    class="underline hover:no-underline whitespace-nowrap"
                    @if (str_starts_with((string) $announcement->link, 'javascript:') || str_starts_with((string) $announcement->link, 'data:'))
                        href="#"
                    @else
                        rel="noopener noreferrer"
                    @endif
                >
                    مشاهده جزئیات
                </a>
            @endif
        </div>
    </div>
@endif