<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\KasAccount;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $masjidId = $request->user()->masjid_id;

        $query = Transaction::with(['kasAccount:id,name', 'category:id,name,type', 'user:id,name'])
            ->where('masjid_id', $masjidId);

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->string('category_id'));
        }
        if ($request->filled('kas_account_id')) {
            $query->where('kas_account_id', $request->string('kas_account_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('from')) {
            $query->whereDate('transaction_date', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('transaction_date', '<=', $request->date('to'));
        }

        $transactions = (clone $query)->latest('transaction_date')->latest('created_at')->paginate(15)->withQueryString();

        $totals = [
            'income' => (clone $query)->where('type', 'income')->where('status', 'approved')->sum('amount'),
            'expense' => (clone $query)->where('type', 'expense')->where('status', 'approved')->sum('amount'),
        ];

        return Inertia::render('Finance/Transactions/Index', [
            'transactions' => $transactions,
            'totals' => $totals,
            'filters' => $request->only(['type', 'category_id', 'kas_account_id', 'status', 'from', 'to']),
            'kasAccounts' => KasAccount::where('masjid_id', $masjidId)->where('is_active', true)->get(['id', 'name']),
            'categories' => TransactionCategory::where('masjid_id', $masjidId)->orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    public function create(Request $request): Response
    {
        $masjidId = $request->user()->masjid_id;

        return Inertia::render('Finance/Transactions/Form', [
            'kasAccounts' => KasAccount::where('masjid_id', $masjidId)->where('is_active', true)->get(['id', 'name']),
            'categories' => TransactionCategory::where('masjid_id', $masjidId)->orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTransaction($request);
        $user = $request->user();

        $proofPath = $request->hasFile('proof_file')
            ? $request->file('proof_file')->store('transactions', 'public')
            : null;

        Transaction::create([
            'masjid_id' => $user->masjid_id,
            'kas_account_id' => $data['kas_account_id'],
            'category_id' => $data['category_id'],
            'user_id' => $user->id,
            'reference_number' => 'TRX-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'type' => $data['type'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'proof_file' => $proofPath,
            'status' => $user->can('finance.approve') ? 'approved' : 'pending',
            'transaction_date' => $data['transaction_date'],
            'notes' => $data['notes'] ?? null,
            'approved_by' => $user->can('finance.approve') ? $user->id : null,
        ]);

        return redirect()->route('admin.finance.transaksi.index')->with('success', 'Transaksi berhasil dicatat.');
    }

    public function edit(Request $request, Transaction $transaksi): Response
    {
        $masjidId = $request->user()->masjid_id;

        return Inertia::render('Finance/Transactions/Form', [
            'transaction' => $transaksi,
            'kasAccounts' => KasAccount::where('masjid_id', $masjidId)->where('is_active', true)->get(['id', 'name']),
            'categories' => TransactionCategory::where('masjid_id', $masjidId)->orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    public function update(Request $request, Transaction $transaksi): RedirectResponse
    {
        $data = $this->validateTransaction($request);

        $proofPath = $request->hasFile('proof_file')
            ? $request->file('proof_file')->store('transactions', 'public')
            : $transaksi->proof_file;

        $transaksi->update([
            'kas_account_id' => $data['kas_account_id'],
            'category_id' => $data['category_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'proof_file' => $proofPath,
            'transaction_date' => $data['transaction_date'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('admin.finance.transaksi.index')->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Transaction $transaksi): RedirectResponse
    {
        $transaksi->delete();

        return back()->with('success', 'Transaksi berhasil dihapus.');
    }

    public function approve(Request $request, Transaction $transaksi): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'notes' => ['nullable', 'string'],
        ]);

        $transaksi->update([
            'status' => $data['status'],
            'approved_by' => $request->user()->id,
            'notes' => $data['notes'] ?? $transaksi->notes,
        ]);

        return back()->with('success', $data['status'] === 'approved' ? 'Transaksi disetujui.' : 'Transaksi ditolak.');
    }

    private function validateTransaction(Request $request): array
    {
        return $request->validate([
            'kas_account_id' => ['required', 'uuid', 'exists:kas_accounts,id'],
            'category_id' => ['required', 'uuid', 'exists:transaction_categories,id'],
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string'],
            'proof_file' => ['nullable', 'file', 'max:5120'],
            'transaction_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
