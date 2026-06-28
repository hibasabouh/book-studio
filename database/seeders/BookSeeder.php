<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\BookSetting;
use App\Models\Chapter;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Give the demo accounts a few books, each with ordered chapters and a
     * settings row, plus one Arabic (RTL) book to showcase that path.
     */
    public function run(): void
    {
        $users = User::whereIn('email', ['admin@bookstudio.test', 'user@bookstudio.test'])->get();

        foreach ($users as $user) {
            Book::factory()->count(2)->for($user)->create()->each(function (Book $book) {
                Chapter::factory()->count(4)->for($book)
                    ->sequence(fn ($seq) => ['order' => $seq->index])
                    ->create();

                BookSetting::factory()->for($book)->create();
            });
        }

        $demo = $users->firstWhere('email', 'user@bookstudio.test');

        if ($demo) {
            $arabic = Book::factory()->for($demo)->create([
                'title' => 'رحلة في عالم الكتابة',
                'description' => 'كتاب تجريبي لعرض دعم اللغة العربية والكتابة من اليمين إلى اليسار.',
            ]);

            collect(['المقدمة', 'الفصل الأول: البداية', 'الفصل الثاني: التحديات'])
                ->each(fn (string $title, int $index) => Chapter::factory()->for($arabic)->create([
                    'title' => $title,
                    'content' => 'هذا نص تجريبي باللغة العربية لعرض كيفية ظهور المحتوى داخل الكتاب.',
                    'order' => $index,
                ]));

            BookSetting::factory()->arabic()->for($arabic)->create(['author' => 'مؤلف تجريبي']);
        }
    }
}
