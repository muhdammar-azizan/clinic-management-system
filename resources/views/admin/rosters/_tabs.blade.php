{{-- List / Calendar switch shared by both roster pages. --}}
<div class="inline-flex rounded-card border border-line bg-white p-0.5 text-sm font-medium">
    <a href="{{ route('admin.rosters.index') }}"
       @class(['rounded px-3 py-1.5', 'bg-primary text-white' => request()->routeIs('admin.rosters.index'), 'text-ink hover:bg-primary-soft' => ! request()->routeIs('admin.rosters.index')])>List</a>
    <a href="{{ route('admin.rosters.calendar') }}"
       @class(['rounded px-3 py-1.5', 'bg-primary text-white' => request()->routeIs('admin.rosters.calendar'), 'text-ink hover:bg-primary-soft' => ! request()->routeIs('admin.rosters.calendar')])>Calendar</a>
</div>
