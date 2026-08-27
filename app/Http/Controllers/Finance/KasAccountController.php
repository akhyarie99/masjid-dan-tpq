<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\KasAccount;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KasAccountController extends Controller
{
    public function index(Request $request): Response
    {
        $accounts = KasAccount::where('masjid_id', $request->user()->masjid_id)
            ->orderBy('name')
            ->get()
            ->map(function (KasAccount $account) {
                $balance = $account->initial_balance
                    + Transaction::where('kas_account_id', $account->id)->where('status', 'approved')->where('type', 'income')->sum('amount')
                    - Transaction::where('kas_account_id', $account->id)->where('status', 'approved')->where('type', 'expense')->sum('amount');

                return [...$account->toArray(), 'current_balance' => (float) $balance];
            });

        return Inertia::render('Finance/Kas/Index', ['accounts' => $accounts]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateAccount($request);

        KasAccount::create([...$data, 'masjid_id' => $request->user()->masjid_id]);

        return back()->with('success', 'Rekening kas berhasil ditambahkan.');
    }

    public function update(Request $request, KasAccount $kas): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $kas);

        $kas->update($this->validateAccount($request));

        return back()->with('success', 'Rekening kas berhasil diperbarui.');
    }

    public function destroy(Request $request, KasAccount $kas): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $kas);

        if (Transaction::where('kas_account_id', $kas->id)->exists()) {
            return back()->with('error', 'Rekening kas tidak dapat dihapus karena memiliki riwayat transaksi.');
        }

        $kas->delete();

        return back()->with('success', 'Rekening kas berhasil dihapus.');
    }

    /**
     * KasAccount tidak punya global scope tenant — route-model binding cuma cari
     * berdasarkan id, jadi tanpa ini pengurus tenant A yang tahu/menebak UUID
     * rekening kas tenant B bisa ubah/hapus lewat URL langsung.
     */
    private function authorizeSameMasjid(Request $request, KasAccount $account): void
    {
        abort_unless($account->masjid_id === $request->user()->masjid_id, 404);
    }

    private function validateAccount(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:cash,bank'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'initial_balance' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}
