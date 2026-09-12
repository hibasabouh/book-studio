<?php

use App\Models\Book;
use App\Models\ContactMessage;
use App\Models\User;

describe('public pages', function () {
    it('renders without authentication', function (string $path, string $view) {
        $this->get($path)->assertOk()->assertViewIs($view);
    })->with([
        'landing' => ['/', 'landing'],
        'about' => ['/about', 'about'],
        'contact' => ['/contact', 'contact'],
        'login' => ['/login', 'auth.login'],
        'register' => ['/register', 'auth.register'],
        'forgot password' => ['/forgot-password', 'auth.forgot-password'],
        // Not behind `auth`: it is the redirect target of the verified middleware.
        'verify email' => ['/email/verify', 'auth.verify-email'],
    ]);

    it('serves the health check', function () {
        $this->get('/up')->assertOk();
    });
});

describe('guest redirects', function () {
    it('sends a guest to the login page', function (string $path) {
        $this->get($path)->assertRedirect(route('login'));
    })->with([
        '/dashboard',
        '/profile',
        '/books',
        '/admin',
        '/admin/users',
        '/admin/messages',
    ]);
});

describe('authenticated pages', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    it('renders the page shell for a signed-in user', function (string $path, string $view) {
        $this->actingAs($this->user)->get($path)->assertOk()->assertViewIs($view);
    })->with([
        'dashboard' => ['/dashboard', 'dashboard.index'],
        'profile' => ['/profile', 'profile.edit'],
        'library' => ['/books', 'books.index'],
    ]);

    it('renders the OTP entry page', function () {
        $this->actingAs(User::factory()->unverified()->create())
            ->get('/email/verify')
            ->assertOk()
            ->assertViewIs('auth.verify-email');
    });
});

describe('book pages', function () {
    it('renders a book the user owns', function () {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $this->actingAs($user)
            ->get("/books/{$book->id}")
            ->assertOk()
            ->assertViewIs('books.show')
            ->assertViewHas('book', fn (Book $viewBook) => $viewBook->is($book));
    });

    it("denies a book belonging to someone else", function () {
        $book = Book::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get("/books/{$book->id}")
            ->assertForbidden();
    });

    it('404s for a book that does not exist', function () {
        $this->actingAs(User::factory()->create())->get('/books/999999')->assertNotFound();
    });
});

describe('admin pages', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
    });

    it('renders the admin shells for an admin', function (string $path, string $view) {
        $this->actingAs($this->admin)->get($path)->assertOk()->assertViewIs($view);
    })->with([
        'overview' => ['/admin', 'admin.index'],
        'users' => ['/admin/users', 'admin.users'],
        'messages' => ['/admin/messages', 'admin.messages'],
    ]);

    it('renders the user details shell with the id it should hydrate', function () {
        $target = User::factory()->create();

        $this->actingAs($this->admin)
            ->get("/admin/users/{$target->id}")
            ->assertOk()
            ->assertViewIs('admin.user-details')
            ->assertViewHas('userId', $target->id);
    });

    it('renders the message details shell with the id it should hydrate', function () {
        $message = ContactMessage::factory()->create();

        $this->actingAs($this->admin)
            ->get("/admin/messages/{$message->id}")
            ->assertOk()
            ->assertViewIs('admin.message-details')
            ->assertViewHas('messageId', $message->id);
    });

    // The list shells share a controller action with the JSON endpoint, so the
    // form request's can:admin check runs before the view branch and blocks them.
    it('denies the list shells to a non-admin', function (string $path) {
        $this->actingAs(User::factory()->create())->get($path)->assertForbidden();
    })->with(['/admin/users', '/admin/messages']);

    // The overview and details shells have no form request, so they render for
    // any signed-in user. They carry no data of their own — every payload comes
    // from the can:admin API endpoints, which a non-admin is refused.
    it('serves the ungated shells to a non-admin, who then gets no data', function () {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertOk();
        $this->actingAs($user)->get("/admin/users/{$target->id}")->assertOk();

        $this->actingAs($user)->getJson('/api/admin/stats')->assertForbidden();
        $this->actingAs($user)->getJson("/api/admin/users/{$target->id}")->assertForbidden();
    });
});
