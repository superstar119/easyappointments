<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.2.0
 * ---------------------------------------------------------------------------- */

require_once __DIR__ . '/API_V1_Controller.php';

use EA\Engine\Api\V1\Request;
use EA\Engine\Api\V1\Response;
use EA\Engine\Types\NonEmptyText;

/**
 * Providers Controller
 *
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Output $output
 * @property CI_Config $config
 * @property CI_Lang $lang
 * @property CI_Cache $cache
 * @property CI_DB_query_builder $db
 * @property CI_Security $security
 * @property Google_Sync $google_sync
 * @property Ics_file $ics_file
 * @property Appointments_model $appointments_model
 * @property Providers_model $providers_model
 * @property Services_model $services_model
 * @property Customers_model $customers_model
 * @property Settings_model $settings_model
 * @property Timezones $timezones
 * @property Roles_model $roles_model
 * @property Secretaries_model $secretaries_model
 * @property Admins_model $admins_model
 * @property User_model $user_model
 *
 * @package Controllers
 */
class Providers extends API_V1_Controller {
    /**
     * Providers Resource Parser
     *
     * @var \EA\Engine\Api\V1\Parsers\Providers
     */
    protected $parser;

    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('providers_model');
        $this->parser = new \EA\Engine\Api\V1\Parsers\Providers;
    }

    /**
     * GET API Method
     *
     * @param int $id Optional (null), the record ID to be returned.
     */
    public function get($id = NULL)
    {
        try
        {
            $condition = $id !== NULL ? 'id = ' . $id : NULL;
            $providers = $this->providers_model->get_batch($condition);

            if ($id !== NULL && count($providers) === 0)
            {
                $this->throw_record_not_found();
            }

            $response = new Response($providers);

            $response->encode($this->parser)
                ->search()
                ->sort()
                ->paginate()
                ->minimize()
                ->singleEntry($id)
                ->output();

        }
        catch (Exception $exception)
        {
            $this->handle_exception($exception);
        }
    }

    /**
     * POST API Method
     */
    public function post()
    {
        try
        {
            // Insert the provider to the database.
            $request = new Request();
            $provider = $request->getBody();
            $this->parser->decode($provider);

            if (isset($provider['id']))
            {
                unset($provider['id']);
            }

            $id = $this->providers_model->add($provider);

            // Fetch the new object from the database and return it to the client.
            $batch = $this->providers_model->get_batch('id = ' . $id);
            $response = new Response($batch);
            $status = new NonEmptyText('201 Created');
            $response->encode($this->parser)->singleEntry(TRUE)->output($status);
        }
        catch (Exception $exception)
        {
            $this->handle_exception($exception);
        }
    }

    /**
     * PUT API Method
     *
     * @param int $id The record ID to be updated.
     */
    public function put($id)
    {
        try
        {
            // Update the provider record.
            $batch = $this->providers_model->get_batch('id = ' . $id);

            if ($id !== NULL && count($batch) === 0)
            {
                $this->throw_record_not_found();
            }

            $request = new Request();
            $updated_provider = $request->getBody();
            $base_provider = $batch[0];
            $this->parser->decode($updated_provider, $base_provider);
            $updated_provider['id'] = $id;
            $id = $this->providers_model->add($updated_provider);

            // Fetch the updated object from the database and return it to the client.
            $batch = $this->providers_model->get_batch('id = ' . $id);
            $response = new Response($batch);
            $response->encode($this->parser)->singleEntry($id)->output();
        }
        catch (Exception $exception)
        {
            $this->handle_exception($exception);
        }
    }

    /**
     * DELETE API Method
     *
     * @param int $id The record ID to be deleted.
     */
    public function delete($id)
    {
        try
        {
            $result = $this->providers_model->delete($id);

            $response = new Response([
                'code' => 200,
                'message' => 'Record was deleted successfully!'
            ]);

            $response->output();
        }
        catch (Exception $exception)
        {
            $this->handle_exception($exception);
        }
    }
}
