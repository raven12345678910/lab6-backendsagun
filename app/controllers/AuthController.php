<?php
defined('PREVENT_DIRECT_ACCESS') or exit('No direct script access allowed');

class AuthController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->api = $this->call->library('api');
        $this->call->library('auth');
    }

    public function register()
    {
        $payload = $this->api->body();
        if (!is_array($payload)) {
            $this->api_error('Validation failed', 422, ['body' => 'A JSON object is required.']);
            return;
        }

        $username = trim((string) ($payload['username'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $password = $payload['password'] ?? '';
        $errors = [];

        if ($username === '' || strlen($username) > 100) {
            $errors['username'] = 'Username is required and must be at most 100 characters.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $errors['email'] = 'Enter a valid email address of at most 150 characters.';
        }
        if (!is_string($password) || strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if (!empty($errors)) {
            $this->api_error('Validation failed', 422, $errors);
            return;
        }

        if ($this->db->table('auth')->where('username', $username)->get()) {
            $this->api_error('Validation failed', 422, ['username' => 'That username is already taken.']);
            return;
        }
        if ($this->db->table('auth')->where('email', $email)->get()) {
            $this->api_error('Validation failed', 422, ['email' => 'That email is already registered.']);
            return;
        }

        if (!$this->auth->register($username, $email, $password) || !$this->auth->login($username, $password)) {
            $this->api_error('Could not create your account.', 500);
            return;
        }

        $this->api_success($this->auth->current_user(), 'Created', 201);
    }

    public function login()
    {
        $payload = $this->api->body();
        $identifier = is_array($payload) ? trim((string) ($payload['identifier'] ?? '')) : '';
        $password = is_array($payload) ? ($payload['password'] ?? '') : '';

        if ($identifier === '' || !is_string($password) || $password === '') {
            $this->api_error('Validation failed', 422, [
                'identifier' => 'Username or email is required.',
                'password' => 'Password is required.',
            ]);
            return;
        }

        if (!$this->auth->login($identifier, $password)) {
            $this->api_error('Invalid username/email or password.', 401);
            return;
        }

        $this->api_success($this->auth->current_user(), 'Signed in.');
    }

    public function me()
    {
        $this->api->respond([
            'success' => true,
            'data' => $this->auth->current_user(),
        ]);
    }

    public function options()
    {
        $this->response->send_no_content();
    }

    public function logout()
    {
        $this->auth->logout();
        $this->api_success(['logged_out' => true], 'Signed out.');
    }

    private function api_success($data = null, $message = 'Success', $status_code = 200)
    {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }

        $this->api->respond($response, $status_code);
    }

    private function api_error($message, $status_code = 400, $errors = null)
    {
        $response = ['success' => false, 'error' => $message];
        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        $this->api->respond($response, $status_code);
    }
}
