<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\LibraryBook;
use App\Models\LibraryLoan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibraryLoanController extends Controller
{
    public function index(Request $request): Response
    {
        $loans = LibraryLoan::with('book:id,title')
            ->whereHas('book', fn ($q) => $q->where('masjid_id', $request->user()->masjid_id))
            ->latest('loan_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Library/Loans', [
            'loans' => $loans,
            'books' => LibraryBook::where('masjid_id', $request->user()->masjid_id)
                ->withCount(['loans as active_loans_count' => fn ($q) => $q->where('status', 'dipinjam')])
                ->orderBy('title')
                ->get()
                ->map(fn (LibraryBook $book) => ['id' => $book->id, 'title' => $book->title, 'available' => $book->stock - $book->active_loans_count])
                ->filter(fn ($book) => $book['available'] > 0)
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'book_id' => ['required', 'uuid', 'exists:library_books,id'],
            'borrower_name' => ['required', 'string', 'max:255'],
            'borrower_phone' => ['required', 'string', 'max:30'],
            'loan_date' => ['required', 'date'],
            'return_date_planned' => ['required', 'date', 'after_or_equal:loan_date'],
        ]);

        $book = LibraryBook::findOrFail($data['book_id']);

        if ($book->availableStock() < 1) {
            return back()->with('error', 'Stok buku tidak tersedia.');
        }

        LibraryLoan::create([...$data, 'status' => 'dipinjam']);

        return back()->with('success', 'Peminjaman buku berhasil dicatat.');
    }

    public function returnBook(LibraryLoan $loan): RedirectResponse
    {
        $loan->update([
            'status' => 'dikembalikan',
            'return_date_actual' => now()->toDateString(),
        ]);

        return back()->with('success', 'Buku berhasil dikembalikan.');
    }

    public function destroy(LibraryLoan $loan): RedirectResponse
    {
        $loan->delete();

        return back()->with('success', 'Data peminjaman berhasil dihapus.');
    }
}
