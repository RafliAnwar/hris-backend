<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;

class EmployeeController extends Controller
{
    public function fetch(Request $request)
    {
        $id = $request->input('id');
        $name = $request->input('name');
        $email = $request->input('email');
        $age = $request->input('age');
        $phone = $request->input('phone');
        $team_id = $request->input('team_id');
        $role_id = $request->input('role_id');
        $limit = $request->input('limit', 10);
        $company_id = $request->input('company_id');


        $employeeQuery = Employee::query();
        //employee?id=$id single data
        if ($id) {
            $employee = $employeeQuery->with(['team', 'role'])->find($id);

            if ($employee) {
                return ResponseFormatter::success($employee, 'Employee found');
            }

            return ResponseFormatter::error('Employee not found', 404);
        }

        $employees = $employeeQuery;
        //employee?name=$name multiple data
        if ($name) {
            $employees->where('name', 'like', '%' . $name . '%');
        }

        if ($age) {
            $employees->where('age', $age);
        }

        if ($email) {
            $employees->where('email', $email);
        }

        if ($phone) {
            $employees->where('phone', 'like', '%' . $phone . '%');
        }

        if ($role_id) {
            $employees->where('role_id', $role_id);
        }

        if ($team_id) {
            $employees->where('team_id', $team_id);
        }
        //employee sort by company through team model that has company_id
        if ($company_id) {
            $employees->whereHas('team', function ($q) use ($company_id) {
                $q->where('company_id', $company_id);
            });
        }

        return ResponseFormatter::success(
            $employees->paginate($limit),
            'Companies found'
        );
    }

    public function create(CreateEmployeeRequest $request)
    {
        try {
            //upload photo
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('public/photos');
            }
            //create employee
            $employee = Employee::create([
                'name' => $request->name,
                'email' => $request->email,
                'gender' => $request->gender,
                'age' => $request->age,
                'phone' => $request->phone,
                'photo' => $path,
                'team_id' => $request->team_id,
                'role_id' => $request->role_id,
            ]);

            if (!$employee) {
                throw new Exception('Employee not created');
            }

            return ResponseFormatter::success($employee, 'Employee created');
        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 500);
        }
    }

    public function update(UpdateEmployeeRequest $request, $id)
    {
        try {
            //get employee
            $employee = Employee::find($id);

            if (!$employee) {
                throw new Exception('Employee not found');
            }
            //upload photo
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('public/photos');
            }
            //update employee
            $employee->update([
                'name' => $request->name,
                'email' => $request->email,
                'gender' => $request->gender,
                'age' => $request->age,
                'phone' => $request->phone,
                'photo' => isset($path) ? $path : $employee->photo,
                'team_id' => $request->team_id,
                'role_id' => $request->role_id,
            ]);

            return ResponseFormatter::success($employee, 'Employee updated');
        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            //get employee
            $employee = Employee::find($id);
            //check if employee exists
            if (!$employee) {
                throw new Exception('Employee not found');
            }
            //delete employee
            $employee->delete();

            return ResponseFormatter::success('Employee deleted');
        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 500);
        }
    }
}
