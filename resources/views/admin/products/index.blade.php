@extends('layouts.admin')
@section('title', 'Manage Products')
@section('content')

    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card shadow no-border mb-0">
                <div class="card-body">
                    <h5 class="mb-4">Filter Products</h5>
                    <form method="GET" class="form-wrapper">
                        <input type="hidden" name="price_range" id="priceRangeInput">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="filterSku">SKU</label>
                                    <input type="text" class="form-control" name="sku" id="filterSku" value="{{ $input['sku'] ?? '' }}" placeholder="SKU">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="filterName">Name</label>
                                    <input type="text" class="form-control" name="name" id="filterName" value="{{ $input['name'] ?? '' }}" placeholder="Name">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="filterStatus">Status</label>
                                    <select class="form-select" name="status" id="filterStatus">
                                        <option value="">All</option>
                                        <option value="active"{{ ($input['status'] ?? '') == 'active'? " selected" : "" }}>Active</option>
                                        <option value="inactive"{{ ($input['status'] ?? '') == 'inactive'? " selected" : "" }}>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="filterFromDate">Date From</label>
                                    <input type="date" class="form-control" name="fdate" id="filterFromDate" value="{{ $input['fdate'] ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="filterToDate">Date To</label>
                                    <input type="date" class="form-control" name="tdate" id="filterToDate" value="{{ $input['tdate'] ?? '' }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="filterPrice">Price Range</label>
                                    <div id="priceRange" class="row col-12">
                                        <div class="form-group col-md-5">
                                            <label class="mb-2" for="filterPriceFrom">From</label>
                                            <input type="number" class="form-control" name="min_price" id="filterPriceFrom" value="{{ $input['min_price'] }}" step="0.01" placeholder="Min">
                                        </div>
                                        <div class="form-group col-md-1 text-center p-3">
                                            <label>-</label>
                                        </div>
                                        <div class="form-group col-md-5">
                                            <label class="mb-2" for="filterPriceTo">To</label>
                                            <input type="number" class="form-control" name="max_price" id="filterPriceTo" value="{{ $input['max_price'] }}" step="0.01" placeholder="Max">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- <div class="form-group col-md-4">
                            <label for="filterPrice">Price Range</label>
                            <div id="priceRangeSlider"></div>
                            <span id="priceRangeValue">{{ $input['min_price'] }} - {{ $input['max_price'] }}</span>
                            <input type="hidden" name="price_range" id="priceRangeInput">
                        </div> -->
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-3">Search</button>
                                <a href="{{ route('admin.products') }}">Clear Search</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-md-12">
            <div class="d-flex justify-content-end align-items-center flex-wrap gap-3">
                <a href="{{ url('/admin/product-daily-prices') }}" class="btn btn-primary me-1">
                    Set Daily Price
                </a>
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary me-1">
                    Add New Product
                </a>
                <a href="{{ url('/admin/products/export' . $query_params) }}" class="btn btn-success">
                    <i class="fa fa-file-excel-o" aria-hidden="true"></i> Export to Excel
                </a>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card shadow no-border mb-0">
                <div class="card-body">
                    <h5 class="mb-4">Products</h5>
                    <div class="table-responsive">
                        <table id="productTable" class="table table-bordered w-100">
                            <thead>
                                <tr>
                                    <th>Option</th>
                                    <th>Image</th>
                                    <th>SKU</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Weight</th>
                                    <th>Status</th>
                                    <th>Last Updated At</th>
                                    <th>Added At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $index => $product)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.products.edit', encrypt($product->id)) }}" class="btn btn-sm btn-primary mb-1" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-danger mb-1" onclick="confirmRemove('{{ $product->id }}')" title="Remove">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                    <td><img src="{{ $product->image_url }}" onError="this.onerror=null;this.src='{{ asset('assets/images/product-default.jpg') }}';" width="80px" /></td>
                                    <td>{{ $product->sku }}</td>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->description }}</td>
                                    <td>{{ $product->price }}</td>
                                    <td>{{ $product->weight ?? 0 }} KG</td>
                                    <td>{{ __('product.status.'.$product->status) }}</td>
                                    <td>{{ $product->updated_at }}</td>
                                    <td>{{ $product->created_at }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="10">
                                        {{ $products->appends(request()->query())->links('pagination::bootstrap-4') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('script')

    <script src="{{ asset('assets/js/sweetalert.min.js') }}"></script>
    <script>
        $(document).ready(function() {            
            // Update range values dynamically
            // var min_price = parseFloat("{{ $input['min_price'] }}");
            // var max_price = parseFloat("{{ $input['max_price'] }}");
            // var from_price = parseFloat("{{ $input['from_price'] }}");
            // var to_price = parseFloat("{{ $input['to_price'] }}");
            // $("#priceRangeSlider").slider({
            //     range: true, // Enable two handles
            //     min: min_price,
            //     max: max_price,
            //     values: [from_price, to_price], // Initial range values
            //     slide: function (event, ui) {
            //         $("#priceRangeValue").text(ui.values[0] + " - " + ui.values[1]);
            //         $("#priceRangeInput").val(ui.values[0] + "," + ui.values[1]);
            //     }
            // });

            // // Initialize the displayed range and input field
            // var initialRange = $("#priceRangeSlider").slider("option", "values");
            // $("#priceRangeValue").text(initialRange[0] + " - " + initialRange[1]);
            // $("#priceRangeInput").val(initialRange[0] + "," + initialRange[1]);

            $("#filterPriceFrom,#filterPriceTo").change(function(e){
                $("#priceRangeInput").val($("#filterPriceFrom").val() + "," + $("#filterPriceTo").val());
            });
        });

        function confirmRemove(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You won\'t be able to revert this!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, remove it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // If the user clicks "Yes," redirect to the removeUrl
                    window.location.href = "{{ url('/admin/product/remove/') }}/" + id;
                }
            });
        }
    </script>

@endsection