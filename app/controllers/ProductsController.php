<?php
defined('PREVENT_DIRECT_ACCESS') or exit('No direct script access allowed');

class ProductsController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->api = $this->call->library('api');
        $this->call->database();
        $this->call->model('Product_model', 'product_model');
        $this->call->library('auth');
    }

    public function index()
    {
        if (!$this->require_authentication()) {
            return;
        }

        $this->api_success($this->product_model->get_all());
    }

    public function show($id)
    {
        if (!$this->require_authentication()) {
            return;
        }

        $product = $this->get_product_or_404((int) $id);
        if ($product === null) {
            return;
        }

        $this->api_success($product);
    }

    public function create()
    {
        if (!$this->require_authentication()) {
            return;
        }

        $data = $this->validated_product_data($this->api->body());
        if ($data === null) {
            return;
        }

        $id = $this->product_model->create_product($data);
        $product = $this->product_model->find_by_id($id);

        header('Location: /api/products/' . $id);
        $this->api_success($product, 'Created', 201);
    }

    public function update($id)
    {
        if (!$this->require_authentication()) {
            return;
        }

        if ($this->get_product_or_404((int) $id) === null) {
            return;
        }

        $data = $this->validated_product_data($this->api->body(), true);
        if ($data === null) {
            return;
        }

        $this->product_model->update_product((int) $id, $data);
        $this->api_success(
            $this->product_model->find_by_id((int) $id),
            'Product updated.'
        );
    }

    public function delete($id)
    {
        if (!$this->require_authentication()) {
            return;
        }

        if ($this->get_product_or_404((int) $id) === null) {
            return;
        }

        $this->product_model->delete_product((int) $id);
        $this->response->send_no_content();
    }

    public function options()
    {
        $this->response->send_no_content();
    }

    private function get_product_or_404($id)
    {
        $product = $this->product_model->find_by_id($id);
        if ($product === null) {
            $this->api_error('Product not found.', 404);
            return null;
        }

        return $product;
    }

    private function require_authentication()
    {
        if ($this->auth->is_logged_in()) {
            return true;
        }

        $this->api_error('Sign in to access products.', 401);
        return false;
    }

    private function validated_product_data($payload, $partial = false)
    {
        [$data, $errors] = $this->validate_product($payload, $partial);
        if (!empty($errors)) {
            $this->api_error('Validation failed', 422, $errors);
            return null;
        }

        return $data;
    }

    private function validate_product($payload, $partial = false)
    {
        if (!is_array($payload)) {
            return [[], ['body' => 'A JSON object is required.']];
        }

        $data = [];
        $errors = [];

        if (!$partial || array_key_exists('product_name', $payload)) {
            $name = $payload['product_name'] ?? null;
            if (!is_string($name) || trim($name) === '') {
                $errors['product_name'] = 'Product name is required.';
            } elseif (strlen(trim($name)) > 100) {
                $errors['product_name'] = 'Product name must be at most 100 characters.';
            } else {
                $data['product_name'] = trim($name);
            }
        }

        if (!$partial || array_key_exists('description', $payload)) {
            $description = $payload['description'] ?? '';
            if (!is_string($description)) {
                $errors['description'] = 'Description must be text.';
            } else {
                $data['description'] = trim($description);
            }
        }

        if (!$partial || array_key_exists('price', $payload)) {
            $price = $payload['price'] ?? null;
            $price_string = is_string($price) || is_numeric($price) ? (string) $price : '';
            if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $price_string)) {
                $errors['price'] = 'Price must be a positive amount with up to 2 decimal places.';
            } else {
                $data['price'] = $price_string;
            }
        }

        if (!$partial || array_key_exists('quantity', $payload)) {
            $quantity = $payload['quantity'] ?? null;
            $quantity_value = filter_var($quantity, FILTER_VALIDATE_INT);
            if ($quantity_value === false || $quantity_value < 0) {
                $errors['quantity'] = 'Quantity must be a non-negative whole number.';
            } else {
                $data['quantity'] = $quantity_value;
            }
        }

        if ($partial && empty($data) && empty($errors)) {
            $errors['body'] = 'At least one product field is required.';
        }

        return [$data, $errors];
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
