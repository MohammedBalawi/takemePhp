<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ __('message.permission') }}</h4>
                        </div>
                        <div class="card-action">
                            <a href="{{ route('home') }}" class="btn border-radius-10 btn-sm btn-primary float-right" role="button"><i class="fas fa-arrow-circle-left"></i> {{ __('message.back') }}</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="mb-3">الحساب الحالي</h5>
                                <p><strong>الاسم:</strong> {{ $doc['name'] ?? '-' }}</p>
                                <p><strong>البريد:</strong> {{ $doc['email'] ?? '-' }}</p>
                                <p><strong>الأدوار:</strong> {{ !empty($roles) ? implode(', ', $roles) : '-' }}</p>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-3">الملف الفعّال</h5>
                                @if(($profile['key'] ?? '') === 'full')
                                    <p class="text-success font-weight-bold">FULL ADMIN</p>
                                    <p>صلاحيات كاملة للوصول والتعديل.</p>
                                @else
                                    <p class="text-warning font-weight-bold">SUB ADMIN (STATS ONLY)</p>
                                    <p>صلاحيات عرض لوحة التحكم فقط.</p>
                                    <ul>
                                        <li>لوحة التحكم</li>
                                        <li>قائمة الصلاحيات</li>
                                    </ul>
                                @endif
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3">إدارة الصلاحيات </h5>
                        <div class="table-responsive">
                            <table class="table table-striped text-center">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الاسم</th>
                                        <th>Email</th>
                                        <th>Roles</th>
                                        <th>الحالة</th>
                                        <th>تحديث</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($admins))
                                        @foreach($admins as $admin)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $admin['name'] ?? '-' }}</td>
                                                <td>{{ $admin['email'] ?? '-' }}</td>
                                                <td>
                                                    {!! Form::open(['route' => ['permissions.update', $admin['email'] ?? ''], 'method' => 'post']) !!}
                                                    <div class="d-flex justify-content-center flex-wrap" style="gap:6px;">
                                                        @php
                                                            $adminRoles = $admin['roles'] ?? [];
                                                        @endphp
                                                        <label class="mr-2">
                                                            <input type="checkbox" name="roles[]" value="super_admin" {{ in_array('super_admin', $adminRoles, true) ? 'checked' : '' }}>
                                                            super_admin
                                                        </label>
                                                        <label class="mr-2">
                                                            <input type="checkbox" name="roles[]" value="admin" {{ in_array('admin', $adminRoles, true) ? 'checked' : '' }}>
                                                            admin
                                                        </label>
                                                        <label class="mr-2">
                                                            <input type="checkbox" name="roles[]" value="sub_admin" {{ in_array('sub_admin', $adminRoles, true) ? 'checked' : '' }}>
                                                            sub_admin
                                                        </label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <label>
                                                        <input type="checkbox" name="is_active" value="1" {{ !empty($admin['is_active']) ? 'checked' : '' }}>
                                                        {{ !empty($admin['is_active']) ? __('message.active') : __('message.inactive') }}
                                                    </label>
                                                </td>
                                                <td>
                                                    <button type="submit" class="btn btn-sm btn-primary">حفظ</button>
                                                    {!! Form::close() !!}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="6">{{ __('message.no_record_found') }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <p class="text-muted mb-0 mt-3">الصلاحيات تُستمد من Firestore وتُحدّث حسب الأدوار المسجلة.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
