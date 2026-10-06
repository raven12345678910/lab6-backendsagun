<?php
defined('PREVENT_DIRECT_ACCESS') or exit('No direct script access allowed');

/**
 * Library: Lauth
 * 
 * Automatically generated via CLI.
 */
class Auth
{
    protected $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->database();
        $this->_lava->call->library('session');
    }

    /**
     * Register a new user
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @return bool
     */
    public function register($username, $email, $password)
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        return $this->_lava->db->table('auth')->insert([
            'username' => $username,
            'email'      => $email,
            'password'   => $hash,
        ]);
    }

    /**
     * Login user
     *
     * @param string $identifier Username or email
     * @param string $password
     * @return bool
     */
    public function login($identifier, $password)
    {
        $user = $this->_lava->db->table('auth')
            ->where('username', $identifier)
            ->or_where('email', $identifier)
            ->get();

        if ($user && password_verify($password, $user['password'])) {
            $this->_lava->session->regenerate_on_login();
            $this->_lava->session->set_userdata([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'logged_in' => true
            ]);
            return true;
        }

        return false;
    }

    /**
     * Get the authenticated user from the current session
     *
     * @return array|null
     */
    public function current_user()
    {
        if (!$this->is_logged_in()) {
            return null;
        }

        return [
            'id' => (int) $this->_lava->session->userdata('user_id'),
            'username' => $this->_lava->session->userdata('username'),
            'email' => $this->_lava->session->userdata('email'),
        ];
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    public function is_logged_in()
    {
        return (bool) $this->_lava->session->userdata('logged_in');
    }

    /**
     * Logout user
     *
     * @return void
     */
    public function logout()
    {
        $this->_lava->session->unset_userdata(['user_id', 'username', 'email', 'logged_in']);
    }
}
