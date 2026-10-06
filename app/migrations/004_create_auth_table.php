<?php

class Create_auth_table
{
    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if ($this->_lava->dbforge->table_exists('auth')) {
            return;
        }

        $this->_lava->dbforge
            ->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'auto_increment' => TRUE,
                    'null' => FALSE,
                ],
                'username' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => FALSE,
                ],
                'email' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => FALSE,
                ],
                'password' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => FALSE,
                ],
            ])
            ->add_key('id', primary: TRUE)
            ->add_key('username', unique: TRUE, name: 'auth_username_unique')
            ->add_key('email', unique: TRUE, name: 'auth_email_unique')
            ->create_table('auth');
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('auth');
    }
}
