@extends('layouts.app')


@section('headline')
    <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate">GenCC Data</h1></div>
      <div class="col-span-2 pt-4 align-bottom">
        <div class="text-right mt-4"><a class="px-3" target="gencc-help" href="https://thegencc.org/resources/help.html#stats-index"><i class="fas fa-question-circle"></i> Help</a></div>
      </div>
  </div>
@endsection

@section('content')
<div class="mt-10">
  <div class="grid grid-cols-2 gap-4 xl:gap-10 mb-6">
        <div>
          <h3>Data Download</h3>
        </div>
        <div>
          <h3>API Access</h3>
          <p class="mb-4">Access to GenCC through and API will be released in early 2021. We recommend anyone interested in access to the API, or would like to be notified when early access is available, to <a href="https://creationproject.us7.list-manage.com/subscribe/post?u=47520fb4e4a2c9edfc44a61af&id=7ccf9c9b09" target="gencc-faq" class="no-underline hover:underline text-blue-800 ">signup</a> so the GenCC to keep you informed.</p>
          <a href="https://creationproject.us7.list-manage.com/subscribe/post?u=47520fb4e4a2c9edfc44a61af&id=7ccf9c9b09" target="gencc-faq" class="no-underline hover:underline text-blue-800 bg-blue-100 rounded-full text-xs py-1 px-2 leading-tight border-2 border-blue-800 ">
							<i class="fas fa-mail-bulk"></i> Stay Informed About GenCC's New Features
						</a>
        </div>
  </div>
</div>

@endsection
