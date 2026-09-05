<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BooksSeeder extends Seeder
{
    public function run(): void
    {
        $catId = [];
        foreach (['fiction', 'science', 'technology'] as $slug) {
            $cat = Category::where('slug', $slug)->first();
            $catId[$slug] = $cat ? $cat->id : 0;
        }

        $books = [
            [
                'id' => 19,
                'category_id' => $catId['fiction'],
                'title' => 'To Kill a Mockingbird',
                'author' => 'Harper Lee',
                'description' => 'Classic novel about justice and race.',
                'price' => 14.99,
                'stock' => 30,
                'stock_threshold' => 10,
                'isbn' => '9780061120084',
                'publisher' => 'J.B. Lippincott',
                'pages' => 336,
                'language' => 'English',
                'format' => 'Paperback',
                'is_featured' => 0,
                'average_rating' => 0.0,
                'status' => 'approved',
                'image' => 'covers/mockingbird.jpg',
                'preview_images' => '["covers/mock1.jpg","covers/mock2.jpg"]',
                'pdf_price' => 9.99,
                'pdf_path' => 'pdfs/mockingbird.pdf',
            ],
            [
                'id' => 20,
                'category_id' => $catId['fiction'],
                'title' => 'Brave New World',
                'author' => 'Aldous Huxley',
                'description' => 'Dystopian futuristic society.',
                'price' => 13.50,
                'stock' => 25,
                'stock_threshold' => 10,
                'isbn' => '9780060850524',
                'publisher' => 'Harper Perennial',
                'pages' => 288,
                'language' => 'English',
                'format' => 'Paperback',
                'is_featured' => 0,
                'average_rating' => 0.0,
                'status' => 'approved',
                'image' => 'covers/brave.jpg',
                'preview_images' => '["covers/brave1.jpg","covers/brave2.jpg"]',
                'pdf_price' => 8.99,
                'pdf_path' => 'pdfs/brave.pdf',
            ],
            [
                'id' => 21,
                'category_id' => $catId['science'],
                'title' => 'A Brief History of Time',
                'author' => 'Stephen Hawking',
                'description' => 'Cosmology explained simply.',
                'price' => 18.99,
                'stock' => 20,
                'stock_threshold' => 10,
                'isbn' => '9780553380163',
                'publisher' => 'Bantam',
                'pages' => 212,
                'language' => 'English',
                'format' => 'Paperback',
                'is_featured' => 0,
                'average_rating' => 0.0,
                'status' => 'approved',
                'image' => 'covers/time.jpg',
                'preview_images' => '["covers/time1.jpg","covers/time2.jpg"]',
            ],
            [
                'id' => 22,
                'category_id' => $catId['science'],
                'title' => 'The Selfish Gene',
                'author' => 'Richard Dawkins',
                'description' => 'Evolution and gene-centered view.',
                'price' => 17.25,
                'stock' => 18,
                'stock_threshold' => 10,
                'isbn' => '9780198788607',
                'publisher' => 'Oxford Press',
                'pages' => 360,
                'language' => 'English',
                'format' => 'Paperback',
                'is_featured' => 0,
                'average_rating' => 0.0,
                'status' => 'approved',
                'image' => 'covers/gene.jpg',
                'preview_images' => '["covers/gene1.jpg","covers/gene2.jpg"]',
            ],
            [
                'id' => 23,
                'category_id' => $catId['technology'],
                'title' => 'Java: The Complete Reference',
                'author' => 'Herbert Schildt',
                'description' => 'Comprehensive Java guide.',
                'price' => 45.00,
                'stock' => 22,
                'stock_threshold' => 10,
                'isbn' => '9781260440232',
                'publisher' => 'McGraw-Hill',
                'pages' => 1248,
                'language' => 'English',
                'format' => 'Hardcover',
                'is_featured' => 0,
                'average_rating' => 0.0,
                'status' => 'approved',
                'image' => 'covers/java.jpg',
                'preview_images' => '["covers/java1.jpg","covers/java2.jpg"]',
            ],
            [
                'id' => 24,
                'category_id' => $catId['technology'],
                'title' => 'Head First Design Patterns',
                'author' => 'Eric Freeman',
                'description' => 'Visual guide to design patterns.',
                'price' => 39.99,
                'stock' => 20,
                'stock_threshold' => 10,
                'isbn' => '9780596007126',
                'publisher' => "O'Reilly",
                'pages' => 694,
                'language' => 'English',
                'format' => 'Paperback',
                'is_featured' => 0,
                'average_rating' => 0.0,
                'status' => 'approved',
                'image' => 'covers/patterns.jpg',
                'preview_images' => '["covers/patterns1.jpg","covers/patterns2.jpg"]',
            ],
        ];

        foreach ($books as $book) {
            $book['preview_images'] = json_decode($book['preview_images'], true);
            Book::updateOrCreate(['id' => $book['id']], $book);
        }
    }
}
