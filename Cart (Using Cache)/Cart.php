<?php
/**
 * @package Cart
 * @author tehcvillage <support@techvill.org>
 * @contributor Sakawat Hossain Rony <[sakawat.techvill@gmail.com]>
 * @created 24-11-2021
 */
namespace App\Cart;
use Validator;
use Cache;
use Auth;

class Cart
{
    /**
     * Added item in cart
     *
     * @param array $data
     * @return bool
     */
    public function add($data = [], $index = null)
    {
        $validator = $this->validate($data);
        if ($validator->fails()) {
            return false;
        }
        $cart = $this->getCartData();
        if (!$cart) {
            $cart[] = [
                    "id" => $data['id'],
                    "item_code" => $data['item_code'],
                    "vendor_id" => $data['vendor_id'],
                    "shop_id" => $data['shop_id'],
                    "name" => $data['name'],
                    "quantity" => $data['quantity'],
                    "price" => $data['price'],
                    "actual_price" => $data['actual_price'],
                    "photo" => $data['photo'],
                    "discount_amount" => $data['discount_amount'],
                    "discount_type" => $data['discount_type'],
                    "option_id" => $data['option_id'],
                    "option_name" => $data['option_name'],
                    "option" => $data['option'],
                 ];
            $this->save($cart);
            $this->destroyCoupon();
            return true;
        } elseif (isset($cart[$index]['id']) && $cart[$index]['id'] == $data['id']) {
            $cart[$index]['quantity'] = $cart[$index]['quantity'] + $data['quantity'];
            $this->save($cart);
            return true;
        } else {
            $cart[] = [
                "id" => $data['id'],
                "item_code" => $data['item_code'],
                "vendor_id" => $data['vendor_id'],
                "shop_id" => $data['shop_id'],
                "name" => $data['name'],
                "quantity" => $data['quantity'],
                "price" => $data['price'],
                "actual_price" => $data['actual_price'],
                "photo" => $data['photo'],
                "discount_amount" => $data['discount_amount'],
                "discount_type" => $data['discount_type'],
                "option_id" => $data['option_id'],
                "option_name" => $data['option_name'],
                "option" => $data['option'],
            ];
            $this->save($cart);
            $this->destroyCoupon();
            return true;
        }
    }

    /**
     * cart item decrement
     *
     * @param $id
     * @return bool|void
     */
    public function reduceQuantity($index)
    {
        $cart = $this->getCartData();
        if (isset($cart[$index])) {
            if ($cart[$index]['quantity'] > 1) {
                $cart[$index]['quantity']--;
                $this->save($cart);
                return true;
            } else {
                $this->destroy($index);
            }
        }
    }

    /**
     * return all cart item
     *
     * @return mixed
     */
    public function getCartData()
    {
        return isset(Auth::user()->id) ? Cache::get(config('cache.prefix') . '-cart_'.Auth::user()->id) : Cache::get(config('cache.prefix') . '-cart_'.getIpAddress());
    }

    /**
     * return coupon
     *
     * @return mixed
     */
    public function getCouponData()
    {
        return isset(Auth::user()->id) ? Cache::get(config('cache.prefix') . '-coupon_'.Auth::user()->id) : Cache::get(config('cache.prefix') . '-coupon_'.getIpAddress());
    }

    /**
     * cart item in collection
     *
     * @return CartCollection
     */
    public function cartCollection()
    {
        return isset(Auth::user()->id) ? new CartCollection(Cache::get(config('cache.prefix') . '-cart_'.Auth::user()->id)) : new CartCollection(Cache::get(config('cache.prefix') . '-cart_'.getIpAddress()));
    }

    /**
     * total item of cart
     *
     * @return int
     */
    public function totalItem()
    {
        $cart = $this->cartCollection();
        return $cart->count();

    }

    /**
     * total quantity of cart
     *
     * @return int|mixed
     */
    public function totalQuantity()
    {
        $cart = $this->cartCollection();
        if ($cart->isEmpty()) return 0;

        $count = $cart->sum(function ($cart) {
            return $cart['quantity'];
        });
        return $count;
    }

    /**
     * total price of cart
     *
     * @return int|mixed
     */
    public function totalPrice()
    {
        $cart = $this->cartCollection();
        if ($cart->isEmpty()) return 0;

        $count = $cart->sum(function ($cart) {
            return $cart['price'] * $cart['quantity'];
        });
        return $count;
    }


    /**
     * validate cart item
     *
     * @param array $item
     * @return mixed
     */
    public function validate($item = [])
    {
        $validator = Validator::make($item, [
            'id' => 'required',
            'item_code' => 'required',
            'price' => 'required|numeric',
            'quantity' => 'required|numeric|min:1',
            'name' => 'required',
            'photo' => 'required'
        ]);

        return $validator;
    }

    /**
     * removes an item on cart by item ID
     *
     * @param $id
     * @return bool
     */
    public function destroy($index = null, $action = 'single')
    {
        if ($action == 'single') {
            $cart = $this->getCartData();
            unset($cart[$index]);
            $this->save($cart);
        } else {
            isset(Auth::user()->id) ? Cache::forget(config('cache.prefix') . '-cart_'.Auth::user()->id) :  Cache::forget(config('cache.prefix') . '-cart_'.getIpAddress());
            $this->destroyCoupon();
        }
        return true;
    }

    /**
     * removes coupon
     *
     * @param $id
     * @return bool
     */
    public function destroyCoupon()
    {
        if (isset(Auth::user()->id) && !empty(Cache::get(config('cache.prefix') . '-coupon_'.Auth::user()->id))) {
            Cache::forget(config('cache.prefix') . '-coupon_'.Auth::user()->id);
        } elseif (!empty(Cache::get(config('cache.prefix') . '-coupon_'.getIpAddress()))) {
                Cache::forget(config('cache.prefix') . '-coupon_'.getIpAddress());
            }
    }

    /**
     * save the cart
     *
     * @param $cart
     * @return bool
     */
    protected function save($cart)
    {
        if (isset(Auth::user()->id)) {
            Cache::put(config('cache.prefix') . '-cart_'.Auth::user()->id, $cart, 30 * 86400);
        } else {
            Cache::put(config('cache.prefix') . '-cart_'.getIpAddress(), $cart, 30 * 86400);
        }
    }

    /**
     * coupon save
     *
     * @param $data
     */
    public function couponSave($data)
    {
        if (isset(Auth::user()->id)) {
            Cache::put(config('cache.prefix') . '-coupon_'.Auth::user()->id, $data, 30 * 86400);
        } else {
            Cache::put(config('cache.prefix') . '-coupon_'.getIpAddress(), $data, 30 * 86400);
        }
    }

    /**
     * item selected
     *
     * @param $data
     */
    public function selectedStore($data = [])
    {
        if (isset(Auth::user()->id)) {
            Cache::put(config('cache.prefix') . '-selected_'.Auth::user()->id, $data, 30 * 86400);
        } else {
            Cache::put(config('cache.prefix') . '-selected_'.getIpAddress(), $data, 30 * 86400);
        }
        $this->destroyCoupon();
    }

    /**
     * get selected item
     *
     * @param $data
     */
    public function getSelected()
    {
        return isset(Auth::user()->id) ? Cache::get(config('cache.prefix') . '-selected_'.Auth::user()->id) : Cache::get(config('cache.prefix') . '-selected_'.getIpAddress());
    }

    /**
     * item selected destroy
     *
     * @param $data
     */
    public function selectedDestroy()
    {
        if (isset(Auth::user()->id) && !empty(Cache::get(config('cache.prefix') . '-selected_'.Auth::user()->id))) {
            Cache::forget(config('cache.prefix') . '-selected_'.Auth::user()->id);
        } elseif (!empty(Cache::get(config('cache.prefix') . '-selected_'.getIpAddress()))) {
            Cache::forget(config('cache.prefix') . '-selected_'.getIpAddress());
        }
    }

    /**
     * cart data transfer local to user
     */
    public function cartDataTransfer()
    {
        if (isset(Auth::user()->id) && empty(Cache::get(config('cache.prefix') . '-cart_'.Auth::user()->id))) {
            if (!empty(Cache::get(config('cache.prefix') . '-cart_'.getIpAddress()))) {
                Cache::put(config('cache.prefix') . '-cart_'.Auth::user()->id, Cache::get(config('cache.prefix') . '-cart_'.getIpAddress()), 30 * 86400);
                if (!empty(Cache::get(config('cache.prefix') . '-selected_'.getIpAddress()))) {
                    Cache::put(config('cache.prefix') . '-selected_'.Auth::user()->id, Cache::get(config('cache.prefix') . '-selected_'.getIpAddress()), 30 * 86400);
                }
            }
        }
    }
}
