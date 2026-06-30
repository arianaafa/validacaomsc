<template>
  <div
    class="relative overflow-hidden rounded-2xl border border-white/60 bg-white/40 p-4 shadow-xl shadow-aura-navy/10 backdrop-blur-md transition duration-200 hover:shadow-2xl hover:shadow-aura-navy/15"
    aria-hidden="true"
  >
    <div class="mb-3 flex items-center justify-between">
      <div>
        <p class="text-[11px] font-semibold uppercase tracking-wider text-aura-blue">Audita MSC</p>
        <p class="text-sm font-semibold text-slate-800">Painel de Validação</p>
      </div>
      <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700">
        98,2% conforme
      </span>
    </div>

    <div class="mb-3 grid grid-cols-3 gap-2">
      <div class="rounded-lg bg-gradient-to-br from-aura-navy to-aura-blue p-2.5 text-white">
        <p class="text-[10px] opacity-80">Competências</p>
        <p class="text-lg font-bold leading-tight">24</p>
      </div>
      <div class="rounded-lg border border-slate-200/80 bg-white/80 p-2.5">
        <p class="text-[10px] text-slate-500">Alertas</p>
        <p class="text-lg font-bold leading-tight text-amber-600">3</p>
      </div>
      <div class="rounded-lg border border-slate-200/80 bg-white/80 p-2.5">
        <p class="text-[10px] text-slate-500">Regras OK</p>
        <p class="text-lg font-bold leading-tight text-emerald-600">156</p>
      </div>
    </div>

    <div class="mb-3 rounded-lg border border-slate-200/80 bg-white/70 p-3">
      <p class="mb-2 text-[10px] font-medium text-slate-500">Conformidade por competência</p>
      <div class="flex h-16 items-end gap-1.5">
        <div class="w-full rounded-sm bg-aura-blue/20" style="height: 45%" />
        <div class="w-full rounded-sm bg-aura-blue/30" style="height: 62%" />
        <div class="w-full rounded-sm bg-aura-blue/40" style="height: 78%" />
        <div class="w-full rounded-sm bg-aura-blue/50" style="height: 55%" />
        <div class="w-full rounded-sm bg-gradient-to-t from-aura-navy to-aura-cyan" style="height: 92%" />
        <div class="w-full rounded-sm bg-aura-blue/35" style="height: 70%" />
      </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200/80 bg-white/70">
      <div class="grid grid-cols-3 gap-2 border-b border-slate-100 bg-slate-50/80 px-2.5 py-1.5 text-[10px] font-semibold text-slate-500">
        <span>Regra</span>
        <span>Status</span>
        <span class="text-right">MSC</span>
      </div>
      <div class="space-y-0 divide-y divide-slate-100 px-2.5 py-1">
        <div class="grid grid-cols-3 gap-2 py-1.5 text-[10px] text-slate-600">
          <span class="truncate">D2_00001</span>
          <span class="text-emerald-600">OK</span>
          <span class="text-right">2025-01</span>
        </div>
        <div class="grid grid-cols-3 gap-2 py-1.5 text-[10px] text-slate-600">
          <span class="truncate">B1_00004</span>
          <span class="text-amber-600">Alerta</span>
          <span class="text-right">2025-01</span>
        </div>
        <div class="grid grid-cols-3 gap-2 py-1.5 text-[10px] text-slate-600">
          <span class="truncate">C3_00012</span>
          <span class="text-emerald-600">OK</span>
          <span class="text-right">2025-01</span>
        </div>
      </div>
    </div>
  </div>
</template>
