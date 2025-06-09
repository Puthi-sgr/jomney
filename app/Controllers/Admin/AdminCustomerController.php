<?php
namespace App\Controllers\Admin;

use App\Core\Response;
use App\Models\Customer;

class AdminCustomerController
{
    private Customer $customerModel;

    public function __construct()
    {
        $this->customerModel = new Customer();
    }

    /**
     * GET /api/admin/customers
     * List all customers
     */
    public function index(): void
    {
        $customers = $this->customerModel->all();
        Response::success('Customers list', $customers);
    }

    /**
     * GET /api/admin/customers/{id}
     * View a single customer
     */
    public function show(int $customerId): void
    {
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            Response::error('Customer not found', [], 404);
            return;
        }
        // Remove password
        unset($customer['password']);
        Response::success('Customer details', $customer);
        return;
    }

      /**
     * DELETE /api/admin/customers/{id}
     * Delete a customer (cascade deletes orders, payment methods, etc.)
     */
    public function delete(int $customerId): void
    {
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            Response::error('Customer not found', [], 404);
            return;
        }
        $result = $this->customerModel->delete($customerId);
        if (!$result) {
            Response::error('Failed to delete customer', [], 500);
            return;
        } 

        Response::success("Customer deleted", [], 200);
        return;
    }
}