<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\LibraryBook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibraryBookController extends Controller
{
    public function index(Request $request): Response
    {
        $query = LibraryBook::where('masjid_id', $request->user()->masjid_id)->withCount([
            'loans as active_loans_count' => fn ($q) => $q->where('status', 'dipinjam'),
        ]);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('author', 'like', "%{$search}%"));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        return Inertia::render('Library/Index', [
            'books' => $query->orderBy('title')->paginate(12)->withQueryString(),
            'filters' => $request->only(['search', 'category']),
            'categories' => LibraryBook::where('masjid_id', $request->user()->masjid_id)->whereNotNull('category')->distinct()->pluck('category'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        LibraryBook::create([...$this->validateBook($request), 'masjid_id' => $request->user()->masjid_id]);

        return back()->with('success', 'Buku berhasil ditambahkan.');
    }

    public function update(Request $request, LibraryBook $buku): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $buku);

        $buku->update($this->validateBook($request));

        return back()->with('success', 'Data buku berhasil diperbarui.');
    }

    public function destroy(Request $request, LibraryBook $buku): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $buku);

        $buku->delete();

        return back()->with('success', 'Buku berhasil dihapus.');
    }

    /**
     * LibraryBook tidak punya global scope tenant — route-model binding cuma
     * cari berdasarkan id, jadi tanpa ini pengurus tenant A yang tahu/menebak
     * UUID buku tenant B bisa ubah/hapus lewat URL langsung.
     */
    private function authorizeSameMasjid(Request $request, LibraryBook $book): void
    {
        abort_unless($book->masjid_id === $request->user()->masjid_id, 404);
    }

    private function validateBook(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:255'],
            'stock' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
