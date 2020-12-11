<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- CSRF Token -->
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<title>{{ $page_meta['seo']['title'] ?? "GenCC" }}</title>

	<!-- Scripts -->

	<!-- Fonts -->
	<link rel="dns-prefetch" href="//fonts.gstatic.com">
	<link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

	<!-- Styles -->
	<link href="{{ asset('css/app.css') }}" rel="stylesheet">
	@livewireStyles

</head>


	{{-- <body class="bg-gray-100 font-sans leading-normal tracking-normal"> --}}
	<body class="bg-white font-sans leading-normal tracking-normal">
		@isset($hide_modal)
			{{-- Workaround --}}
		@else
			@if(Session::get('modal.welcome.dismiss') != true)
				@livewire('modal.welcome')
			@endif
		@endisset
		@include('partials.navs.header-nav')

	<!--Container-->
	@hasSection ('headline')
		<div class=" bg-blue-800">
			<div class="container w-full mx-auto pt-20 mt-4 pb-1 text-white">
				@yield('headline')
			</div>
		</div>
	@endif
	@hasSection ('banner')
		<div class=" bg-blue-700">
			<div class="container w-full mx-auto pt-4 text-white">
				@yield('banner')
			</div>
		</div>
	@endif
	<div class="container w-full mx-auto @hasSection('headline')  pt-0 @else  pt-20 @endif">

		<div class="w-full px-2 md:px-0 md:mt-6 mb-16 text-gray-900 leading-normal">

		   @yield('content')


		</div>
</div>

<div class="w-full bg-gray-300">
		<div class="container w-full mx-auto py-3  text-sm">
			<div class="mt-5 mb-5">
				<p class="">
					@include("partials.terms.general-disclaimer")
				</p>
			</div>
			{{-- <ul class=" my-8">
				<li><i class="fas fa-angle-double-right"></i> The information on this website is not intended for diagnostic use or medical decision-making without review by a genetics professional.</li>
				<li><i class="fas fa-angle-double-right"></i> Individuals should not change their health behavior solely on the basis of information obtained on this website.</li>
				<li><i class="fas fa-angle-double-right"></i> If you have questions about the information obtained on this website, please see a health care professional.</li>
				<li><i class="fas fa-angle-double-right"></i> The GenCC does not accept medical questions and/or provide advice. All medical questions should be directed to your health care professional.</li>
				<li><i class="fas fa-angle-double-right"></i> If you contact the GenCC, do not share any information containing protected health information (PHI). <a href="https://clinicalgenome.org/share-your-data/" target="_blank">Click here to learn how to share data.</a></li>
			</ul> --}}

			<div class="pb-5">
				<hr class="border-1 border-gray-200 my-6" />
				&copy; 2020 <a href="https://thegencc.org/" class="underline">The GenCC</a> - All rights reserved.
							<a href="{{ route('reset-modal') }}" class="float-right text-sm text-muted px-2 underline"> GenCC Welcome Statement</a>
							<a href="https://thegencc.org/privacy.html" target="_blank" class="float-right text-sm text-muted px-2 underline"> GenCC Website Privacy Policy</a>
							<a href="https://thegencc.org/terms.html" target="_blank" class="float-right text-sm text-muted px-2 underline">GenCC Terms of Use</a>
			</div>

	</div>
</div>
	<!--/container-->

	{{-- <footer class="bg-white border-t border-gray-400 shadow">
		<div class="container max-w-md mx-auto flex py-8">

			<div class="w-full mx-auto flex flex-wrap">
				<div class="flex w-full md:w-1/2 ">
					<div class="px-8">
						<h3 class="font-bold font-bold text-gray-900">About</h3>
						<p class="py-4 text-gray-600 text-sm">
						</p>
					</div>
				</div>
				<div class="flex w-full md:w-1/2">
					<div class="px-8">
						<h3 class="font-bold font-bold text-gray-900">Curation</h3>

					</div>
				</div>
			</div>



		</div>
	</footer> --}}

	<script src="{{ asset('js/app.js') }}"></script>
	<script src="/packages/jquery-3-4-1/jquery.min.js"></script>
  <script src="/packages/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="/packages/bootstrap-4.3.1-js/bootstrap.bundle.js"></script>
	<script type="text/javascript">
	$(function () {
			$('[data-toggle="tooltip"]').tooltip()
		})

	document.addEventListener("livewire:load", function(event) {
		// window.livewire.hook('beforeDomUpdate', () => {
    //         // Add your custom JavaScript here.
    //     });
		// window.livewire.hook('afterDomUpdate', () => {

		// 	});
		});

		//$('[data-toggle="tooltip"]').on('hover').tooltip()
		// $('button').on('click', 'filter', function () {
		// 	alert("sdfdf");
		// 	$(function () {
		// 		$('[data-toggle="tooltip"]').tooltip()
		// 	})
		// });
		$(function () {
			$('[data-toggle="tooltip"]').tooltip()
		})

    window.setTimeout(function() {
      $(".alert.fade").fadeTo(500, 0).slideUp(500, function() {
        $(this).remove();
      });
		}, 2000);


function toggle_visibility(id) {
  var e = document.getElementById(id);
  if (e.style.display == 'block')
    e.style.display = 'none';
  else
    e.style.display = 'block';
}
  </script>

	@livewireScripts
	{{-- <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.7.x/dist/alpine.min.js" defer></script> --}}
</body>

</html>