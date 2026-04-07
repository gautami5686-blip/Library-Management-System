<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use App\Services\ActivityLogger;
use PDO;

final class BookController extends Controller
{
    public function index(array $params = []): never
    {
        $stmt = $this->db->query(
            'SELECT id, title, author, genre, isbn, published_year, description,
                    copies_total, copies_available, created_at, updated_at
             FROM books
             ORDER BY title ASC'
        );

        $this->ok(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function search(array $params = []): never
    {
        $query = $this->query();
        $term = trim((string) ($query['q'] ?? ''));
        $genre = trim((string) ($query['genre'] ?? ''));

        $sql = 'SELECT id, title, author, genre, isbn, published_year, copies_available
                FROM books
                WHERE 1=1';
        $params = [];

        if ($term !== '') {
            $sql .= ' AND (title LIKE :term OR author LIKE :term OR isbn LIKE :term)';
            $params['term'] = '%' . $term . '%';
        }

        if ($genre !== '') {
            $sql .= ' AND genre LIKE :genre';
            $params['genre'] = '%' . $genre . '%';
        }

        $sql .= ' ORDER BY title ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $this->ok(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function show(array $params): never
    {
        $stmt = $this->db->prepare('SELECT * FROM books WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $params['id']]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$book) {
            $this->ok(['message' => 'Book not found'], 404);
        }

        $this->ok(['data' => $book]);
    }

    public function store(array $params = []): never
    {
        $payload = $this->input();
        $this->requireFields($payload, ['title', 'author', 'genre', 'copies_total']);

        $copiesTotal = max(1, (int) $payload['copies_total']);
        $stmt = $this->db->prepare(
            'INSERT INTO books (title, author, genre, isbn, published_year, description, copies_total, copies_available)
             VALUES (:title, :author, :genre, :isbn, :published_year, :description, :copies_total, :copies_available)'
        );
        $stmt->execute([
            'title' => trim((string) $payload['title']),
            'author' => trim((string) $payload['author']),
            'genre' => trim((string) $payload['genre']),
            'isbn' => $payload['isbn'] ?? null,
            'published_year' => $payload['published_year'] ?? null,
            'description' => $payload['description'] ?? null,
            'copies_total' => $copiesTotal,
            'copies_available' => $copiesTotal,
        ]);

        $bookId = (int) $this->db->lastInsertId();
        ActivityLogger::log((int) $this->currentUser()['id'], 'created', 'book', $bookId);

        $this->ok(['message' => 'Book created successfully', 'book_id' => $bookId], 201);
    }

    public function update(array $params): never
    {
        $payload = $this->input();
        $bookId = (int) $params['id'];

        $stmt = $this->db->prepare('SELECT * FROM books WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $bookId]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$book) {
            $this->ok(['message' => 'Book not found'], 404);
        }

        $copiesTotal = isset($payload['copies_total']) ? max(1, (int) $payload['copies_total']) : (int) $book['copies_total'];
        $issuedCount = (int) $book['copies_total'] - (int) $book['copies_available'];
        if ($copiesTotal < $issuedCount) {
            $this->ok(['message' => 'copies_total cannot be less than currently issued copies'], 422);
        }

        $update = $this->db->prepare(
            'UPDATE books
             SET title = :title, author = :author, genre = :genre, isbn = :isbn,
                 published_year = :published_year, description = :description,
                 copies_total = :copies_total, copies_available = :copies_available
             WHERE id = :id'
        );
        $update->execute([
            'id' => $bookId,
            'title' => $payload['title'] ?? $book['title'],
            'author' => $payload['author'] ?? $book['author'],
            'genre' => $payload['genre'] ?? $book['genre'],
            'isbn' => $payload['isbn'] ?? $book['isbn'],
            'published_year' => $payload['published_year'] ?? $book['published_year'],
            'description' => $payload['description'] ?? $book['description'],
            'copies_total' => $copiesTotal,
            'copies_available' => $copiesTotal - $issuedCount,
        ]);

        ActivityLogger::log((int) $this->currentUser()['id'], 'updated', 'book', $bookId);
        $this->ok(['message' => 'Book updated successfully']);
    }

    public function destroy(array $params): never
    {
        $bookId = (int) $params['id'];

        $stmt = $this->db->prepare('SELECT * FROM books WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $bookId]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$book) {
            $this->ok(['message' => 'Book not found'], 404);
        }

        if ((int) $book['copies_available'] !== (int) $book['copies_total']) {
            $this->ok(['message' => 'Cannot delete a book while copies are still issued'], 422);
        }

        $this->db->prepare('DELETE FROM books WHERE id = :id')->execute(['id' => $bookId]);
        ActivityLogger::log((int) $this->currentUser()['id'], 'deleted', 'book', $bookId);
        $this->ok(['message' => 'Book deleted successfully']);
    }
}
