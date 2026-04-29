<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Mailer;

class PageController extends Controller
{
    public function about(): void
    {
        $this->view('pages/about');
    }

    public function contact(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
            header('Location: /login');
            exit;
        }

        $this->view('pages/contact');
    }

    public function sendContact(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
            header('Location: /login');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        $errors = [];

        if ($name === '') {
            $errors[] = 'Name is required.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }

        if ($subject === '') {
            $errors[] = 'Subject is required.';
        }

        if ($message === '') {
            $errors[] = 'Message is required.';
        }

        if (!empty($errors)) {
            $this->view('pages/contact', ['errors' => $errors]);
            return;
        }

        $db = Database::connect();
        $userId = (int) $_SESSION['user']['id'];

        $stmt = mysqli_prepare(
            $db,
            "INSERT INTO contact_messages
            (user_id, name, email, subject, message, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())"
        );

        mysqli_stmt_bind_param($stmt, "issss", $userId, $name, $email, $subject, $message);
        mysqli_stmt_execute($stmt);

        $sellerEmail = 'ogbeideprince80@gmail.com';

        $emailBody = "
        <h2>New Contact Message</h2>

        <p><strong>Name:</strong> {$name}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Subject:</strong> {$subject}</p>

        <hr>

        <p><strong>Message:</strong></p>
        <p>" . nl2br(htmlspecialchars($message)) . "</p>
        ";


















        try {
            Mailer::send($sellerEmail, 'New Contact Message: ' . $subject, $emailBody);
        } catch (\Throwable $e) {
            $this->view('pages/contact', [
                'errors' => ['Message saved, but email could not be sent. Please check Mailer settings.']
            ]);
            return;
        }

        $_SESSION['contact_success'] = 'Your message has been sent successfully.';
        header('Location: /contact');
        exit;
    }

    public function reviews(): void
    {
        $db = Database::connect();

        $sql = "
            SELECT
                r.id,
                r.rating,
                r.title,
                r.review_text,
                r.created_at,
                u.full_name
            FROM reviews r
            INNER JOIN users u ON r.user_id = u.id
            WHERE r.status = 'approved'
            ORDER BY r.id DESC
        ";

        $result = mysqli_query($db, $sql);
        $reviews = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $success = $_SESSION['review_success'] ?? null;
        unset($_SESSION['review_success']);

        $this->view('pages/reviews', [
            'reviews' => $reviews,
            'success' => $success
        ]);
    }

    public function storeReview(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
            header('Location: /login');
            exit;
        }

        $rating = (int) ($_POST['rating'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $reviewText = trim($_POST['review_text'] ?? '');

        $errors = [];

        if ($rating < 1 || $rating > 5) {
            $errors[] = 'Please select a rating between 1 and 5.';
        }

        if ($title === '') {
            $errors[] = 'Review title is required.';
        }

        if ($reviewText === '') {
            $errors[] = 'Review message is required.';
        }

        if (!empty($errors)) {
            $db = Database::connect();
            $result = mysqli_query($db, "
                SELECT r.*, u.full_name
                FROM reviews r
                INNER JOIN users u ON r.user_id = u.id
                WHERE r.status = 'approved'
                ORDER BY r.id DESC
            ");
            $reviews = mysqli_fetch_all($result, MYSQLI_ASSOC);

            $this->view('pages/reviews', [
                'errors' => $errors,
                'reviews' => $reviews
            ]);
            return;
        }

        $db = Database::connect();
        $userId = (int) $_SESSION['user']['id'];

        $stmt = mysqli_prepare(
            $db,
            "INSERT INTO reviews
            (user_id, rating, title, review_text, status, created_at)
            VALUES (?, ?, ?, ?, 'approved', NOW())"
        );

        mysqli_stmt_bind_param($stmt, "iiss", $userId, $rating, $title, $reviewText);
        mysqli_stmt_execute($stmt);

        $_SESSION['review_success'] = 'Your review has been added successfully.';
        header('Location: /reviews');
        exit;
    }
}