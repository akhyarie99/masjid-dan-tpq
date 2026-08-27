<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetLoan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetLoanController extends Controller
{
    public function index(Request $request): Response
    {
        $loans = AssetLoan::with(['asset:id,name,asset_code', 'requester:id,name', 'approver:id,name'])
            ->whereHas('asset', fn ($q) => $q->where('masjid_id', $request->user()->masjid_id))
            ->latest('loan_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Asset/Loan/Index', ['loans' => $loans]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Asset/Loan/Form', [
            'assets' => Asset::where('masjid_id', $request->user()->masjid_id)->where('status', 'aktif')->orderBy('name')->get(['id', 'name', 'asset_code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asset_id' => ['required', 'uuid', 'exists:assets,id'],
            'borrower_name' => ['required', 'string', 'max:255'],
            'borrower_phone' => ['required', 'string', 'max:30'],
            'purpose' => ['required', 'string', 'max:255'],
            'loan_date' => ['required', 'date'],
            'return_date_planned' => ['required', 'date', 'after_or_equal:loan_date'],
            'condition_out' => ['required', 'in:baik,cukup,rusak_ringan'],
        ]);

        AssetLoan::create([
            ...$data,
            'requested_by' => $request->user()->id,
            'status' => 'pending',
        ]);

        return redirect()->route('admin.asset.peminjaman.index')->with('success', 'Pengajuan peminjaman berhasil dibuat.');
    }

    public function approve(Request $request, AssetLoan $loan): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $loan);

        $loan->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
        ]);

        $loan->asset->update(['status' => 'dipinjam']);

        return back()->with('success', 'Peminjaman disetujui.');
    }

    public function returnAsset(Request $request, AssetLoan $loan): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $loan);

        $data = $request->validate([
            'condition_in' => ['required', 'in:baik,cukup,rusak_ringan,rusak_berat'],
        ]);

        $loan->update([
            'status' => 'returned',
            'return_date_actual' => now()->toDateString(),
            'condition_in' => $data['condition_in'],
        ]);

        $loan->asset->update([
            'status' => 'aktif',
            'condition' => $data['condition_in'],
        ]);

        return back()->with('success', 'Aset berhasil dikembalikan.');
    }

    public function destroy(Request $request, AssetLoan $loan): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $loan);

        $loan->delete();

        return back()->with('success', 'Data peminjaman berhasil dihapus.');
    }

    /**
     * AssetLoan tidak punya kolom masjid_id maupun global scope tenant —
     * kepemilikannya diturunkan dari aset yang dipinjam. Tanpa ini pengurus
     * tenant A yang tahu/menebak UUID peminjaman tenant B bisa
     * menyetujui/mengembalikan/menghapusnya lewat URL langsung.
     */
    private function authorizeSameMasjid(Request $request, AssetLoan $loan): void
    {
        abort_unless($loan->asset?->masjid_id === $request->user()->masjid_id, 404);
    }
}
