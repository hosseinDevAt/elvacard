@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-300 focus:border-[#010619] focus:ring-2 focus:ring-[#ffde5b]/60 rounded-xl text-sm text-slate-800 shadow-sm transition']) }}>
