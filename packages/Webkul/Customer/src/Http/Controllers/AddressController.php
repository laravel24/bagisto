<?php

namespace Webkul\Customer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Auth;

/**
 * Customer controlller for the customer basically for the tasks of customers which will
 * be done after customer authenticastion.
 *
 * @author    Prashant Singh <prashant.singh852@webkul.com>
 * @copyright 2018 Webkul Software Pvt Ltd (http://www.webkul.com)
 */
class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $_config;

    protected $customer;

    protected $address;

    public function __construct(
        CustomerRepository $customer,
        CustomerAddressRepository $address
    )
    {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->customer = auth()->guard('customer')->user();

        $this->address = $address;
    }

    /**
     * Address Route index page
     *
     * @return view
     */
    public function index()
    {
        return view($this->_config['view'])->with('addresses', $this->customer->addresses);
    }

    /**
     * Show the address create form
     *
     * @return view
     */
    public function create()
    {
        return view($this->_config['view']);
    }

    /**
     * Create a new address for customer.
     *
     * @return view
     */
    public function store()
    {
        $data = collect(request()->input())->except('_token')->toArray();

        $this->validate(request(), [
            'address1' => 'string|required',
            'country' => 'string|required',
            'state' => 'string|required',
            'city' => 'string|required',
            'postcode' => 'required',
            'phone' => 'required'
        ]);

        $cust_id['customer_id'] = $this->customer->id;
        $data = array_merge($cust_id, $data);

        if($this->customer->addresses->count() == 0) {
            $data['default_address'] = 1;
        }

        if($this->address->create($data)) {
            session()->flash('success', 'Address have been successfully added.');

            return redirect()->route($this->_config['redirect']);
        } else {
            session()->flash('error', 'Address cannot be added.');

            return redirect()->back();
        }
    }

    /**
     * For editing the existing addresses of current logged in customer
     *
     * @return view
     */
    public function edit($id)
    {
        $address = $this->address->find($id);

        return view($this->_config['view'], compact('address'));
    }

    /**
     * Edit's the premade resource of customer called
     * Address.
     *
     * @return redirect
     */
    public function update($id)
    {
        $this->validate(request(), [
            'address1' => 'string|required',
            'country' => 'string|required',
            'state' => 'string|required',
            'city' => 'string|required',
            'postcode' => 'required'
        ]);

        $data = collect(request()->input())->except('_token')->toArray();

        $this->address->update($data, $id);

        Session()->flash('success','Address Updated Successfully.');

        return redirect()->route('customer.address.index');
    }

    /**
     * To change the default address or make the default address, by default when first address is created will be the default address
     *
     * @return Response
     */
    public function makeDefault($id)
    {
        if($default = $this->customer->default_address) {
            $this->address->find($default->id)->update(['default_address' => 0]);
        }

        if($address = $this->address->find($id)) {
            $address->update(['default_address' => 1]);
        } else {
            session()->flash('success', 'Default Cannot Be Address Changed');
        }

        return redirect()->back();
    }

    /**
     * Delete address of the current customer
     *
     * @param integer $id
     *
     * @return response mixed
     */
    public function destroy($id)
    {
        $this->address->delete($id);

        session()->flash('success', trans('shop::app.address.delete.success'));

        return redirect()->back();
    }
}
