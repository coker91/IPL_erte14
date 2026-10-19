<x-filament-panels::page>
 <x-filament::section heading="Ajukan Pembayaran">
  <p>Rekap lama dari Google Script adalah catatan status. Jika tagihan belum tersedia, hubungi bendahara untuk menetapkan nominal dan menerbitkannya.</p>
  <form wire:submit="submit" style="margin-top:1rem">
   {{ $this->form }}
   @error('payment') <p role="alert">{{ $message }}</p> @enderror
   <x-filament::button type="submit" wire:loading.attr="disabled" style="margin-top:1rem">Kirim bukti pembayaran</x-filament::button>
  </form>
 </x-filament::section>
 <x-filament::section heading="30 Pengajuan Terakhir Saya">
  <div style="overflow:auto"><table style="width:100%;text-align:left"><thead><tr><th>Referensi</th><th>Rumah</th><th>Nominal</th><th>Status</th><th>Bukti & Catatan</th></tr></thead><tbody>
   @forelse($this->history() as $p)
   <tr><td>{{ $p->reference_no }}</td><td>{{ $p->house->code }}</td><td>Rp {{ number_format($p->amount,0,',','.') }}</td><td>{{ ['submitted'=>'Menunggu verifikasi','approved'=>'Disetujui','rejected'=>'Ditolak','reversed'=>'Dibatalkan'][$p->status] ?? $p->status }}</td><td>
    @foreach($p->proofs as $proof)<a href="{{ route('proofs.show',$proof) }}" target="_blank" rel="noopener">Resi {{ $loop->iteration }}</a> @endforeach
    @foreach($p->reviews as $review)<p>{{ $review->reason }}</p>@endforeach
   </td></tr>
   @empty<tr><td colspan="5">Belum ada pengajuan.</td></tr>@endforelse
  </tbody></table></div>
 </x-filament::section>
</x-filament-panels::page>
