@if (Auth::check())
    <!-- Left side column. contains the sidebar -->
    <aside class="main-sidebar">
      <!-- sidebar: style can be found in sidebar.less -->
      <section class="sidebar">
        <!-- Sidebar user panel -->
        @include('backpack::inc.sidebar_user_panel')

        <!-- sidebar menu: : style can be found in sidebar.less -->
        <ul class="sidebar-menu">
          {{-- <li class="header">{{ trans('backpack::base.administration') }}</li> --}}
          <!-- ================================================ -->
          <!-- ==== Recommended place for admin menu items ==== -->
          <!-- ================================================ -->
          <li><a href="{{ backpack_url('dashboard') }}"><i class="fa fa-dashboard"></i> <span>{{ trans('backpack::base.dashboard') }}</span></a></li>
              @if(Auth::user()->hasPermissionTo('list users'))
                <li><a href="{{ backpack_url('user') }}"><i class="fa fa-user"></i> <span>Users</span></a></li>
              @endif
              @if(Auth::user()->hasPermissionTo('list expert-panels'))
                  <li><a href="{{ backpack_url('expert-panel') }}"><i class="fa fa-users"></i> <span>Expert Panels</span></a></li>
              @endif
              @if(Auth::user()->hasPermissionTo('list working-groups'))
                <li><a href="{{ backpack_url('working-group') }}"><i class="fa fa-tasks"></i> <span>Working Groups</span></a></li>
              @endif
              @if(Auth::user()->hasPermissionTo('list curation-types'))
                <li><a href="{{ backpack_url('curation-type') }}"><i class="fa fa-star"></i> <span>Curation Types</span></a></li>
              @endif
              @if(Auth::user()->hasPermissionTo('list pages'))
                <li><a href="{{ url(config('backpack.base.route_prefix').'/page') }}"><i class="fa fa-file-o"></i> <span>Pages</span></a></li>
              @endif

          <!-- ======================================= -->
          {{-- <li class="header">Other menus</li> --}}
        </ul>
      </section>
      <!-- /.sidebar -->
    </aside>
@endif
