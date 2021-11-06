<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Services API v1 controller.
 *
 * @package Controllers
 */
class Services_api_v1 extends EA_Controller {
    /**
     * Services_api_v1 constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('services_model');

        $this->load->library('api');

        $this->api->cors();

        $this->api->auth();

        $this->api->model('services_model');
    }

    /**
     * Get an service collection.
     */
    public function index()
    {
        try
        {
            $keyword = $this->api->request_keyword();

            $limit = $this->api->request_limit();

            $offset = $this->api->request_offset();

            $order_by = $this->api->request_order_by();

            $fields = $this->api->request_fields();

            $services = empty($keyword)
                ? $this->services_model->get(NULL, $limit, $offset, $order_by)
                : $this->services_model->search($keyword, $limit, $offset, $order_by);

            foreach ($services as &$service)
            {
                $this->services_model->api_encode($service);

                if ( ! empty($fields))
                {
                    $this->services_model->only($service, $fields);
                }
            }

            json_response($services);
        }
        catch (Throwable $e)
        {
            json_exception($e);
        }
    }

    /**
     * Get a single service.
     *
     * @param int|null $id Service ID.
     */
    public function show(int $id = NULL)
    {
        try
        {
            $fields = $this->api->request_fields();

            $service = $this->services_model->find($id);

            $this->services_model->api_encode($service);

            if ( ! empty($fields))
            {
                $this->services_model->only($service, $fields);
            }

            if ( ! $service)
            {
                response('', 404);

                return;
            }

            json_response($service);
        }
        catch (Throwable $e)
        {
            json_exception($e);
        }
    }

    /**
     * Create an service.
     */
    public function store()
    {
        try
        {
            $service = request();

            $this->services_model->api_decode($service);

            if (array_key_exists('id', $service))
            {
                unset($service['id']);
            }

            $service_id = $this->services_model->save($service);

            $created_service = $this->services_model->find($service_id);

            $this->services_model->api_encode($created_service);

            json_response($created_service, 201);
        }
        catch (Throwable $e)
        {
            json_exception($e);
        }
    }

    /**
     * Update an service.
     *
     * @param int $id Service ID.
     */
    public function update(int $id)
    {
        try
        {
            $occurrences = $this->services_model->get(['id' => $id]);

            if (empty($occurrences))
            {
                response('', 404);

                return;
            }

            $original_service = $occurrences[0];

            $service = request();

            $this->services_model->api_decode($service, $original_service);

            $service_id = $this->services_model->save($service);

            $updated_service = $this->services_model->find($service_id);

            $this->services_model->api_encode($updated_service);

            json_response($updated_service);
        }
        catch (Throwable $e)
        {
            json_exception($e);
        }
    }

    /**
     * Delete an service.
     *
     * @param int $id Service ID.
     */
    public function destroy(int $id)
    {
        try
        {
            $occurrences = $this->services_model->get(['id' => $id]);

            if (empty($occurrences))
            {
                response('', 404);

                return;
            }

            $this->services_model->delete($id);

            response('', 204);
        }
        catch (Throwable $e)
        {
            json_exception($e);
        }
    }
}
