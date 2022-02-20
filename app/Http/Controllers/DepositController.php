<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DepositController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function reset(): Response
    {
        Deposit::truncate();
        return response('OK',200);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $account_id = $request->input('account_id');
        $deposit = Deposit::where('account_id', $account_id)->first();
        if ($deposit) {
            return response($deposit->balance, 200);
        } else {
            return response(0, 404);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function event(Request $request): JsonResponse|Response
    {
        $type = $request->input('type');
        $amount = $request->input('amount');
        if ($type === 'deposit') {
            $destination = $request->input('destination');
            $deposit = Deposit::firstOrNew([
                'account_id' => $destination
            ]);
            if ($deposit->exists) {
                $deposit->balance += $amount;
            } else {
                $deposit->balance = $amount;
            }
            $deposit->save();
            return response()->json([
                'destination' => [
                    'id' => $destination,
                    'balance' => $deposit->balance
                ],
            ], 201);
        } else if ($type === 'withdraw') {
            $origin = $request->input('origin');
            $deposit = Deposit::where('account_id', $origin)->first();
            if ($deposit) {
                $deposit->balance -= $amount;
                $deposit->save();
                return response()->json([
                    'origin' => [
                        'id' => $origin,
                        'balance' => $deposit->balance
                    ],
                ], 201);
            } else {
                return response(0, 404);
            }

        } else if ($type === 'transfer') {
            $origin = $request->input('origin');
            $destination = $request->input('destination');
            $origin_deposit = Deposit::where('account_id', $origin)->first();
            if ($origin_deposit) {
                $destination_deposit = Deposit::firstOrNew([
                    'account_id' => $destination
                ]);
                $origin_deposit->balance -= $amount;
                $destination_deposit->balance += $amount;
                $origin_deposit->save();
                $destination_deposit->save();
                return response()->json([
                    'origin' => [
                        'id' => $origin,
                        'balance' => $origin_deposit->balance
                    ],
                    'destination' => [
                        'id' => $destination,
                        'balance' => $destination_deposit->balance
                    ],
                ], 201);
            } else {
                return response(0, 404);
            }
        }
    }
}
