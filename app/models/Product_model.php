<?php
defined('PREVENT_DIRECT_ACCESS') or exit('No direct script access allowed');

class Product_model extends Model
{
    protected $table = 'products';

    public function get_all()
    {
        return $this->db->table($this->table)
            ->select('id, product_name, description, price, quantity, created_at')
            ->order_by('id', 'ASC')
            ->get_all();
    }

    public function find_by_id($id)
    {
        return $this->db->table($this->table)
            ->select('id, product_name, description, price, quantity, created_at')
            ->where('id', $id)
            ->get() ?: null;
    }

    public function create_product($data)
    {
        $this->db->table($this->table)->insert($data);
        return $this->db->last_id();
    }

    public function update_product($id, $data)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->update($data);
    }

    public function delete_product($id)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->delete();
    }
}
