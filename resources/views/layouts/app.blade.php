<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- CSRF Token -->
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<title>{{ $page_meta['seo']['title'] ?? "GenCC" }}</title>
	<link rel="icon" type="image/x-icon" href="/favicon.ico">

	<!-- Scripts -->

	<!-- Fonts -->
	<link rel="dns-prefetch" href="//fonts.gstatic.com">
	<link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

	<!-- Styles -->
	<link href="{{ asset('css/app.css') }}" rel="stylesheet">
	@livewireStyles

  <!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-Y9ZHWJMZNK"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());

		gtag('config', 'G-Y9ZHWJMZNK');
	</script>

</head>


	<body class="bg-white font-sans leading-normal tracking-normal">
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
				<p class="mb-2">The GenCC data are available free of restriction under a CC0 1.0 Universal (CC0 1.0) Public Domain Dedication. The GenCC requests that you give attribution to GenCC and the contributing sources whenever possible and appropriate. The accepted Flagship manuscript is now available from Genetics in Medicine (<a href="https://www.gimjournal.org/article/S1098-3600(22)00746-8/fulltext" target="_manu">https://www.gimjournal.org/article/S1098-3600(22)00746-8/fulltext</a>).</p>
				@include("partials.terms.general-disclaimer")
			</div>

			<div class="pb-5">
				<hr class="border-1 border-gray-200 my-6" />
				&copy; 2026 <a href="{{ url('/') }}" class="underline">The GenCC</a> - All rights reserved.
				<a href="{{ url('/privacy') }}" target="_blank" class="float-right text-sm text-muted px-2 underline"> GenCC Website Privacy Policy</a>
				<a href="{{ url('/terms') }}" target="_blank" class="float-right text-sm text-muted px-2 underline">GenCC Terms of Use</a>
			</div>

	</div>
</div>
	<script src="{{ asset('js/app.js') }}"></script>
	<script src="/packages/jquery-3-4-1/jquery.min.js"></script>
    <script src="/packages/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="/packages/bootstrap-4.3.1-js/bootstrap.bundle.js"></script>
	<script type="text/javascript">
		$(function () {
			$('[data-toggle="tooltip"]').tooltip()
		});

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
</body>

</html>
