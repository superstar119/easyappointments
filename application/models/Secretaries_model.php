<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Secretaries model
 *
 * Handles all the database operations of the secretary resource.
 *
 * @package Models
 */
class Secretaries_model extends EA_Model {
    /**
     * Secretaries_model constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('password');
        $this->load->helper('validation');
    }

    /**
     * Save (insert or update) a secretary.
     *
     * @param array $secretary Associative array with the secretary data.
     *
     * @return int Returns the secretary ID.
     *
     * @throws InvalidArgumentException
     */
    public function save(array $secretary): int
    {
        $this->validate($secretary);

        if (empty($secretary['id']))
        {
            return $this->insert($secretary);
        }
        else
        {
            return $this->update($secretary);
        }
    }

    /**
     * Validate the secretary data.
     *
     * @param array $secretary Associative array with the secretary data.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $secretary): void
    {
        // If a secretary ID is provided then check whether the record really exists in the database.
        if ( ! empty($provider['id']))
        {
            $count = $this->db->get_where('users', ['id' => $secretary['id']])->num_rows();

            if ( ! $count)
            {
                throw new InvalidArgumentException('The provided secretary ID does not exist in the database: ' . $secretary['id']);
            }
        }

        // Make sure all required fields are provided. 
        if (
            empty($secretary['first_name'])
            || empty($secretary['last_name'])
            || empty($secretary['email'])
            || empty($secretary['phone_number'])
        )
        {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($secretary, TRUE));
        }

        // Validate the email address.
        if ( ! filter_var($secretary['email'], FILTER_VALIDATE_EMAIL))
        {
            throw new InvalidArgumentException('Invalid email address provided: ' . $secretary['email']);
        }

        // Validate secretary providers.
        if (empty($provider['providers']) || ! is_array($provider['providers']))
        {
            throw new InvalidArgumentException('The provided secretary providers are invalid: ' . print_r($secretary, TRUE));
        }
        else
        {
            // Make sure the provided provider entries are numeric values.
            foreach ($secretary['providers'] as $secretary_id)
            {
                if ( ! is_numeric($secretary_id))
                {
                    throw new InvalidArgumentException('The provided secretary providers are invalid: ' . print_r($secretary, TRUE));
                }
            }
        }

        // Make sure the username is unique. 
        if ( ! empty($secretary['settings']['username']))
        {
            $secretary_id = $secretary['id'] ?? NULL;

            if ( ! $this->validate_username($secretary['settings']['username'], $secretary_id))
            {
                throw new InvalidArgumentException('The provided username is already in use, please use a different one.');
            }
        }

        // Validate the password. 
        if ( ! empty($secretary['settings']['password']))
        {
            if (strlen($secretary['settings']['password']) < MIN_PASSWORD_LENGTH)
            {
                throw new InvalidArgumentException('The secretary password must be at least ' . MIN_PASSWORD_LENGTH . ' characters long.');
            }
        }

        // New users must always have a password value set. 
        if (empty($secretary['id']) && empty($secretary['settings']['password']))
        {
            throw new InvalidArgumentException('The provider password cannot be empty when inserting a new record.');
        }

        // Validate calendar view type value.
        if (
            ! empty($secretary['settings']['calendar_view'])
            && ! in_array($secretary['settings']['calendar_view'], [CALENDAR_VIEW_DEFAULT, CALENDAR_VIEW_TABLE])
        )
        {
            throw new InvalidArgumentException('The provided calendar view is invalid: ' . $secretary['settings']['calendar_view']);
        }

        // Make sure the email address is unique.
        $secretary_id = $secretary['id'] ?? NULL;

        $count = $this
            ->db
            ->select()
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('roles.slug', DB_SLUG_SECRETARY)
            ->where('users.email', $secretary['email'])
            ->where('users.id !=', $secretary_id)
            ->get()
            ->num_rows();

        if ($count > 0)
        {
            throw new InvalidArgumentException('The provided email address is already in use, please use a different one.');
        }
    }

    /**
     * Validate the secretary username.
     *
     * @param string $username Secretary username.
     * @param int|null $secretary_id Secretary ID.
     *
     * @return bool Returns the validation result.
     */
    public function validate_username(string $username, int $secretary_id = NULL): bool
    {
        if ( ! empty($secretary_id))
        {
            $this->db->where('id_users !=', $secretary_id);
        }

        return $this->db->get_where('user_settings', ['username' => $username])->num_rows() === 0;
    }

    /**
     * Insert a new secretary into the database.
     *
     * @param array $secretary Associative array with the secretary data.
     *
     * @return int Returns the secretary ID.
     *
     * @throws RuntimeException
     */
    protected function insert(array $secretary): int
    {
        $secretary['id_roles'] = $this->get_secretary_role_id();

        $providers = $secretary['providers'];
        unset($secretary['providers']);

        $settings = $secretary['settings'];
        unset($secretary['settings']);


        if ( ! $this->db->insert('users', $secretary))
        {
            throw new RuntimeException('Could not insert secretary.');
        }

        $secretary['id'] = $this->db->insert_id();
        $settings['salt'] = generate_salt();
        $settings['password'] = hash_password($settings['salt'], $settings['password']);

        $this->save_settings($secretary['id'], $settings);
        $this->save_provider_ids($secretary['id'], $providers);

        return $secretary['id'];
    }

    /**
     * Update an existing secretary.
     *
     * @param array $secretary Associative array with the secretary data.
     *
     * @return int Returns the secretary ID.
     *
     * @throws RuntimeException
     */
    protected function update(array $secretary): int
    {
        $provider_ids = $secretary['providers'];
        unset($secretary['providers']);

        $settings = $secretary['settings'];
        unset($secretary['settings']);

        if (isset($settings['password']))
        {
            $existing_settings = $this->db->get_where('user_settings', ['id_users' => $secretary['id']])->row_array();

            if (empty($existing_settings))
            {
                throw new RuntimeException('No settings record found for secretary with ID: ' . $secretary['id']);
            }

            $settings['password'] = hash_password($existing_settings['salt'], $settings['password']);
        }

        if ( ! $this->db->update('users', $secretary, ['id' => $secretary['id']]))
        {
            throw new RuntimeException('Could not update secretary.');
        }

        $this->save_settings($secretary['id'], $settings);
        $this->save_provider_ids($secretary['id'], $provider_ids);

        return (int)$secretary['id'];
    }

    /**
     * Remove an existing secretary from the database.
     *
     * @param int $secretary_id Provider ID.
     *
     * @throws RuntimeException
     */
    public function delete(int $secretary_id): void
    {
        if ( ! $this->db->delete('users', ['id' => $secretary_id]))
        {
            throw new RuntimeException('Could not delete secretary.');
        }
    }

    /**
     * Get a specific secretary from the database.
     *
     * @param int $secretary_id The ID of the record to be returned.
     *
     * @return array Returns an array with the secretary data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $secretary_id): array
    {
        if ( ! $this->db->get_where('users', ['id' => $secretary_id])->num_rows())
        {
            throw new InvalidArgumentException('The provided secretary ID was not found in the database: ' . $provider_id);
        }

        $secretary = $this->db->get_where('users', ['id' => $secretary_id])->row_array();

        $secretary['settings'] = $this->db->get_where('user_settings', ['id_users' => $secretary_id])->row_array();

        unset(
            $secretary['settings']['id_users'],
            $secretary['settings']['password'],
            $secretary['settings']['salt']
        );

        $secretary_provider_connections = $this->db->get_where('secretaries_providers', ['id_users_secretary' => $secretary_id])->result_array();

        $secretary['providers'] = [];

        foreach ($secretary_provider_connections as $secretary_provider_connection)
        {
            $secretary['providers'][] = (int)$secretary_provider_connection['id_users_provider'];
        }

        return $secretary;
    }

    /**
     * Get a specific field value from the database.
     *
     * @param int $secretary_id Secretary ID.
     * @param string $field Name of the value to be returned.
     *
     * @return string Returns the selected secretary value from the database.
     *
     * @throws InvalidArgumentException
     */
    public function value(int $secretary_id, string $field): string
    {
        if (empty($field))
        {
            throw new InvalidArgumentException('The field argument is cannot be empty.');
        }

        if (empty($secretary_id))
        {
            throw new InvalidArgumentException('The secretary ID argument cannot be empty.');
        }

        // Check whether the secretary exists.
        $query = $this->db->get_where('users', ['id' => $secretary_id]);

        if ( ! $query->num_rows())
        {
            throw new InvalidArgumentException('The provided secretary ID was not found in the database: ' . $provider_id);
        }

        // Check if the required field is part of the secretary data.
        $secretary = $query->row_array();

        if ( ! array_key_exists($field, $secretary))
        {
            throw new InvalidArgumentException('The requested field was not found in the secretary data: ' . $field);
        }

        return $secretary[$field];
    }

    /**
     * Get all secretaries that match the provided criteria.
     *
     * @param array|string $where Where conditions
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of secretaries.
     */
    public function get($where = NULL, int $limit = NULL, int $offset = NULL, string $order_by = NULL)
    {
        $role_id = $this->get_secretary_role_id();

        if ($where !== NULL)
        {
            $this->db->where($where);
        }

        if ($order_by !== NULL)
        {
            $this->db->order_by($order_by);
        }

        $secretaries = $this->db->get_where('users', ['id_roles' => $role_id], $limit, $offset)->result_array();

        foreach ($secretaries as &$secretary)
        {
            $secretary['settings'] = $this->db->get_where('user_settings', ['id_users' => $secretary['id']])->row_array();

            unset(
                $secretary['settings']['id_users'],
                $secretary['settings']['password'],
                $secretary['settings']['salt']
            );

            $secretary_provider_connections = $this->db->get_where('secretaries_providers', ['id_users_secretary' => $secretary['id']])->result_array();

            $secretary['providers'] = [];

            foreach ($secretary_provider_connections as $secretary_provider_connection)
            {
                $secretary['providers'][] = (int)$secretary_provider_connection['id_users_provider'];
            }
        }

        return $secretaries;
    }

    /**
     * Get the secretary role ID.
     *
     * @return int Returns the role ID.
     */
    public function get_secretary_role_id(): int
    {
        $role = $this->db->get_where('roles', ['slug' => DB_SLUG_SECRETARY])->row_array();

        if (empty($role))
        {
            throw new RuntimeException('The secretary role was not found in the database.');
        }

        return $role['id'];
    }

    /**
     * Save the secretary settings.
     *
     * @param int $secretary_id Secretary ID.
     * @param array $settings Associative array with the settings data.
     *
     * @throws InvalidArgumentException
     */
    protected function save_settings(int $secretary_id, array $settings): void
    {
        if (empty($settings))
        {
            throw new InvalidArgumentException('The settings argument cannot be empty.');
        }

        // Make sure the settings record exists in the database. 
        $count = $this->db->get_where('user_settings', ['id_users' => $secretary_id])->num_rows();

        if ( ! $count)
        {
            $this->db->insert('user_settings', ['id_users' => $secretary_id]);
        }

        foreach ($settings as $name => $value)
        {
            $this->set_setting($secretary_id, $name, $value);
        }
    }

    /**
     * Set the value of a secretary setting.
     *
     * @param int $secretary_id Secretary ID.
     * @param string $name Setting name.
     * @param string $value Setting value.
     */
    public function set_setting(int $secretary_id, string $name, string $value): void
    {
        if ( ! $this->db->update('user_settings', [$name => $value], ['id_users' => $secretary_id]))
        {
            throw new RuntimeException('Could not set the new secretary setting value: ' . $name);
        }
    }

    /**
     * Get the value of a secretary setting.
     *
     * @param int $secretary_id Secretary ID.
     * @param string $name Setting name.
     *
     * @return string Returns the value of the requested user setting.
     */
    public function get_setting(int $secretary_id, string $name): string
    {
        $settings = $this->db->get_where('user_settings', ['id_users' => $secretary_id])->row_array();

        if (empty($settings[$name]))
        {
            throw new RuntimeException('The requested setting value was not found: ' . $secretary_id);
        }

        return $settings[$name];
    }

    /**
     * Save the secretary provider IDs.
     *
     * @param int $secretary_id Secretary ID.
     * @param array $provider_ids Provider IDs.
     */
    protected function save_provider_ids(int $secretary_id, array $provider_ids): void
    {
        // Re-insert the secretary-provider connections. 
        $this->db->delete('secretary_providers', ['id_users_secretary' => $secretary_id]);

        foreach ($provider_ids as $provider_id)
        {
            $secretary_provider_connection = [
                'id_users_secretary' => $secretary_id,
                'id_users_provider' => $provider_id
            ];

            $this->db->insert('secretaries_providers', $secretary_provider_connection);
        }
    }

    /**
     * Get the query builder interface, configured for use with the users (secretary-filtered) table.
     *
     * @return CI_DB_query_builder
     */
    public function query(): CI_DB_query_builder
    {
        $role_id = $this->get_secretary_role_id();

        return $this->db->from('users')->where('id_roles', $role_id);
    }

    /**
     * Search secretaries by the provided keyword.
     *
     * @param string $keyword Search keyword.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of secretaries.
     */
    public function search(string $keyword, int $limit = NULL, int $offset = NULL, string $order_by = NULL): array
    {
        $role_id = $this->get_secretary_role_id();

        return $this
            ->db
            ->select()
            ->from('users')
            ->where('id_roles', $role_id)
            ->like('first_name', $keyword)
            ->or_like('last_name', $keyword)
            ->or_like('email', $keyword)
            ->or_like('phone_number', $keyword)
            ->or_like('mobile_number', $keyword)
            ->or_like('address', $keyword)
            ->or_like('city', $keyword)
            ->or_like('state', $keyword)
            ->or_like('zip_code', $keyword)
            ->or_like('notes', $keyword)
            ->limit($limit)
            ->offset($offset)
            ->order_by($order_by)
            ->get()
            ->result_array();
    }

    /**
     * Attach related resources to a secretary.
     *
     * @param array $secretary Associative array with the secretary data.
     * @param array $resources Resource names to be attached ("providers" supported).
     *
     * @throws InvalidArgumentException
     */
    public function attach(array &$secretary, array $resources): void
    {
        if (empty($secretary) || empty($resources))
        {
            return;
        }

        foreach ($resources as $resource)
        {
            switch ($resource)
            {
                case 'providers':
                    $secretary['providers'] = $this
                        ->db
                        ->select('users.*')
                        ->from('users')
                        ->join('secretaries_providers', 'secretaries_providers.id_users_provider = users.id', 'inner')
                        ->where('id_users_secretary', $secretary['id'])
                        ->get()
                        ->result_array();
                    break;

                default:
                    throw new InvalidArgumentException('The requested secretary relation is not supported: ' . $resource);
            }
        }
    }
}
