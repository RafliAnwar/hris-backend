<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;

class CompanyController extends Controller
{
    public function fetch(Request $request)
    {
        $id = $request->input('id');
        $name = $request->input('name');
        $limit = $request->input('limit', 10);
        $companyQuery = Company::with(['users'])->whereHas('users', function ($query) {
            $query->where('user_id', Auth::id());
        });
        //company?id=$id
        if ($id) {
            $company = $companyQuery->find($id);

            if ($company) {
                return ResponseFormatter::success($company, 'Company found');
                //without response formatter
                // return response()->json([
                //     'meta' => [
                //         'code' => 200,
                //         'status' => 'success',
                //         'message' => "Company found",
                //     ],
                //     'result' => [
                //         'id'=>$company->id,
                //         'name' => $company->name,
                //         'logo' => $company->logo,
                //         'created_at' => $company->created_at,
                //         'users' => $company->users,
                //     ],
                // ], 200);
            }

            return ResponseFormatter::error('Company not found', 404);
        }

        $companies = $companyQuery;
        //company?name=$name
        if ($name) {
            $companies->where('name', 'like', '%' . $name . '%');
        }

        return ResponseFormatter::success(
            $companies->paginate($limit),
            'Companies found'
        );

        // without response formatter
        // $resultArray = [];
        // foreach ($companies->paginate($limit) as $c) {
        //     $resultArray[] = [ 
        //         'id' => $c->id,
        //         'name' => $c->name,
        //         'logo' => $c->logo,
        //         'created_at' => $c->created_at,
        //         'users' => $c->users,
        //     ];
        // }

        // return response()->json([
        //     'meta' => [
        //         'code' => 200,
        //         'status' => 'success',
        //         'message' => "Company found",
        //     ],
        //     'result' => $resultArray
        // ], 200);
    }

    public function create(CreateCompanyRequest $request)
    {
        try {
            //upload logo
            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('public/logos');
            }
            //create company
            $company = Company::create([
                'name' => $request->name,
                'logo' => $path,
            ]);

            if (!$company) {
                throw new Exception('Company not created');
            }
            //attach company to user
            $user = User::find(Auth::id());
            $user->companies()->attach($company->id);
            //load users at company
            $company->load('users');

            return ResponseFormatter::success($company, 'Company created');
        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 500);
        }
    }

    public function update(UpdateCompanyRequest $request, $id)
    {
        try {
            //get company
            $company = Company::find($id);

            if (!$company) {
                throw new Exception('Company not found');
            }
            //upload logo
            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('public/logos');
            }
            //update company
            $company->update([
                'name' => $request->name,
                'logo' => isset($path) ? $path : $company->logo,
            ]);

            return ResponseFormatter::success($company, 'Company updated');
        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 500);
        }
    }
}
