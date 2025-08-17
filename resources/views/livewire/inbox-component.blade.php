<div class="col-lg-9 col-md-8">
    <div class="card bg-white">
        <div class="card-body">
            <div class="email-header">
                <div class="row">
                    <div class="col top-action-left">
                        <div class="float-start">
                            <div class="btn-group dropdown-action">
                                <button type="button" class="btn btn-white-outline dropdown-toggle"
                                    data-bs-toggle="dropdown">Select <i class="fas fa-angle-down"></i></button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#">All</a>
                                    <a class="dropdown-item" href="#">None</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#">Read</a>
                                    <a class="dropdown-item" href="#">Unread</a>
                                </div>
                            </div>
                            <div class="btn-group dropdown-action">
                                <button type="button" class="btn btn-white-outline dropdown-toggle"
                                    data-bs-toggle="dropdown">Actions <i class="fas fa-angle-down"></i></button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#">Reply</a>
                                    <a class="dropdown-item" href="#">Forward</a>
                                    <a class="dropdown-item" href="#">Archive</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#">Mark As Read</a>
                                    <a class="dropdown-item" href="#">Mark As Unread</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#">Delete</a>
                                </div>
                            </div>
                            <div class="btn-group dropdown-action">
                                <button type="button" class="btn btn-white-outline dropdown-toggle"
                                    data-bs-toggle="dropdown"><i class="fas fa-folder"></i> <i
                                        class="fas fa-angle-down"></i></button>
                                <div role="menu" class="dropdown-menu">
                                    <a class="dropdown-item" href="#">Social</a>
                                    <a class="dropdown-item" href="#">Forums</a>
                                    <a class="dropdown-item" href="#">Updates</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#">Spam</a>
                                    <a class="dropdown-item" href="#">Trash</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#">New</a>
                                </div>
                            </div>
                            <div class="btn-group dropdown-action">
                                <button type="button" data-bs-toggle="dropdown"
                                    class="btn btn-white-outline dropdown-toggle"><i class="fas fa-tags"></i> <i
                                        class="fas fa-angle-down"></i></button>
                                <div role="menu" class="dropdown-menu">
                                    <a class="dropdown-item" href="#">Work</a>
                                    <a class="dropdown-item" href="#">Family</a>
                                    <a class="dropdown-item" href="#">Social</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#">Primary</a>
                                    <a class="dropdown-item" href="#">Promotions</a>
                                    <a class="dropdown-item" href="#">Forums</a>
                                </div>
                            </div>
                            <div class="btn-group dropdown-action mail-search">
                                <input type="text" placeholder="Search Messages" class="form-control search-message">
                            </div>
                        </div>
                    </div>
                    <div class="col-auto top-action-right">
                        <div class="text-end">
                            <button type="button" title="Refresh" data-bs-toggle="tooltip"
                                class="btn btn-white-outline d-none d-md-inline-block"><i
                                    class="fas fa-sync-alt"></i></button>
                            <div class="btn-group">
                                <a class="btn btn-white-outline"><i class="fas fa-angle-left"></i></a>
                                <a class="btn btn-white-outline"><i class="fas fa-angle-right"></i></a>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="text-muted d-none d-md-inline-block">Showing 10 of 112 </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="email-content">
                <div class="table-responsive">
                    <table class="table table-inbox table-hover">
                        <thead>
                            <tr>
                                <th colspan="6" class="py-3">
                                    <input type="checkbox" class="checkbox-all">
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $json = file_get_contents(public_path('../public/assets/json/inbox.json'));
                                $indexs = json_decode($json, true);
                            @endphp
                            @foreach ($indexs as $index)
                                <tr class="{{ $index['Class'] }}">
                                    <td>
                                        <input type="checkbox" class="checkmail">
                                    </td>
                                    <td><span class="mail-important"><i class="{{ $index['StarClass'] }}"></i></span>
                                    </td>
                                    <td class="name">{{ $index['Name'] }}</td>
                                    <td class="subject">{{ $index['Content'] }}</td>
                                    <td>
                                        @if ($index['Name'] === 'John Doe' || $index['Name'] === 'Richard Miles')
                                            <i class="fas fa-paperclip"></i>
                                        @endif
                                    </td>
                                    <td class="mail-date">{{ $index['Time'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
