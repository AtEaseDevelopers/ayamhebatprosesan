<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Driver;
use Illuminate\Support\Facades\DB;

class LorryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth_admin');
    }

    public function index(Request $req)
    {
        $data = [
            'type' => $req->query('type') == null || $req->query('type') == 'unarchive' ? 'archive' : 'unarchive',
        ];
        return view('admin.drivers.index', $data);
    }

    public function create()
    {
        return view('admin.drivers.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'lorry_number' => 'required',
        ]);

        Driver::create([
            'lorry_number' => $request->lorry_number,
        ]);

        return redirect(route('admin.lorry.index'))->with('success', 'Driver added successfully.');
    }

    public function edit($id)
    {
        $data['driver'] = Driver::where('id', decrypt($id))->first();
        return view('admin.drivers.edit', $data);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'lorry_number' => 'required',
        ]);

        Driver::where('id', decrypt($id))->update([
            'lorry_number' => $request->lorry_number,
        ]);

        return redirect(route('admin.lorry.index'))->with('success', 'Driver updated successfully.');
    }

    public function destroy($id)
    {
        Driver::where('id', decrypt($id))->delete();

        return redirect(route('admin.lorry.index'))->with('success', 'Driver deleted successfully.');
    }

    public function get_lorry(Request $request)
    {
        $show_archive = $request->query('type') == 'archive';
        $columns = array('id', 'options', 'lorry_number', 'created_at');

        $totalitems = DB::table('drivers')->where('archived', $show_archive)->count();
        $totalFiltered = $totalitems;
        if ($request->input('length') == -1) {
            $limit =  $totalitems;
        } else {
            $limit = $request->input('length');
        }
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        if (empty($request->input('search.value'))) {
            $records = DB::table('drivers')
                ->select('id', 'lorry_number', 'created_at', 'archived')
                ->where('archived',  $show_archive)
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
        } else {
            $search = $request->input('search.value');
            $records = DB::table('drivers')
                ->select('id', 'lorry_number', 'created_at', 'archived')
                ->where('lorry_number', 'LIKE', "%{$search}%")
                ->where('archived', $show_archive)
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $totalFiltered = $records->count();
        }

        $data = array();
        if (!empty($records)) {
            foreach ($records as $record) {
                $nestedData['id'] = $record->id;
                $nestedData['lorry_number'] = $record->lorry_number;
                $nestedData['created_at'] = date('m-d-Y', strtotime($record->created_at));   
                $nestedData['options'] = '
                    <a href="' . route('admin.lorry.edit', encrypt($record->id)) . '" class="btn btn-sm btn-primary">
                        <i class="fa fa-edit"></i>
                    </a>
                    <button type="button" title="Archive" class="btn btn-sm btn-danger btn-archive" data-action="' . route('admin.lorry.archive', encrypt($record->id)) . '" data-bs-toggle="modal" data-bs-target="#archive">
                        <i class="fa fa-thumb-tack"></i>
                    </button>
                ';
                if ($show_archive) {
                    $nestedData['options'] .= '<button type="button" title="Unarchive" class="btn btn-sm btn-secondary btn-unarchive" data-action="' . route('admin.lorry.unarchive', encrypt($record->id)) . '" data-bs-toggle="modal" data-bs-target="#unarchive">
                        <i class="fa fa-undo"></i>
                    </button>';
                }
                $data[] = $nestedData;
            }
        }

        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalitems),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        );

        echo json_encode($json_data);
    }
    
    public function archive($id)
    {
        Driver::where('id', decrypt($id))->update([
            'archived' => true,
        ]);

        return redirect(route('admin.lorry.index'))->with('success', 'Driver archived successfully.');
    }
    
    public function unarchive($id)
    {
        Driver::where('id', decrypt($id))->update([
            'archived' => false,
        ]);

        return redirect(route('admin.lorry.index'))->with('success', 'Driver unarchived successfully.');
    }
}
