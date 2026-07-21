<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(Request $request): Response
    {
        $budgets = Budget::where('masjid_id', $request->user()->masjid_id)
            ->withCount('items')
            ->withSum('items', 'planned_amount')
            ->latest('start_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Finance/Budget/Index', ['budgets' => $budgets]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Finance/Budget/Form', [
            'categories' => TransactionCategory::where('masjid_id', $request->user()->masjid_id)->orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateBudget($request);

        $budget = Budget::create([
            'masjid_id' => $request->user()->masjid_id,
            'name' => $data['name'],
            'period_type' => $data['period_type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => $data['status'],
        ]);

        $budget->items()->createMany($data['items']);

        return redirect()->route('admin.finance.anggaran.index')->with('success', 'Anggaran (RAB) berhasil dibuat.');
    }

    public function edit(Request $request, Budget $anggaran): Response
    {
        $anggaran->load('items');

        $realizations = Transaction::where('masjid_id', $request->user()->masjid_id)
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$anggaran->start_date, $anggaran->end_date])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        return Inertia::render('Finance/Budget/Form', [
            'budget' => $anggaran,
            'realizations' => $realizations,
            'categories' => TransactionCategory::where('masjid_id', $request->user()->masjid_id)->orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    public function update(Request $request, Budget $anggaran): RedirectResponse
    {
        $data = $this->validateBudget($request);

        $anggaran->update([
            'name' => $data['name'],
            'period_type' => $data['period_type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => $data['status'],
        ]);

        $anggaran->items()->delete();
        $anggaran->items()->createMany($data['items']);

        return redirect()->route('admin.finance.anggaran.index')->with('success', 'Anggaran (RAB) berhasil diperbarui.');
    }

    public function destroy(Budget $anggaran): RedirectResponse
    {
        $anggaran->delete();

        return back()->with('success', 'Anggaran (RAB) berhasil dihapus.');
    }

    private function validateBudget(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'period_type' => ['required', 'in:monthly,yearly,project'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:draft,active,closed'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'uuid', 'exists:transaction_categories,id'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.planned_amount' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);
    }
}
