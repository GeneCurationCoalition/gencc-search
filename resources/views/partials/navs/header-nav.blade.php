	<nav id="header" class="bg-white fixed w-full z-30 top-0 shadow">


		<div class="w-full container mx-auto flex flex-wrap items-center mt-0 pt-3 pb-3 md:pb-0">

			<div class="w-1/3 pl-2 mb-4 md:pl-0">
				<a href="https://thegencc.org/faq.html#public-beta"><img src="/brand/icons/beta.png" class=" hover:scale-150  transition duration-200 ease-in-out transform  absolute w-10 ml-40 mt-1" /></a>
				<a class="text-gray-900 text-base xl:text-xl no-underline hover:no-underline font-bold" href="{{ route('home')}}">
				 <img src="/brand/logo/genecc-logo.jpg" class=" h-16" />
				</a>
			</div>
			<div class="w-2/3 pr-0">
				<div class="flex relative inline-block float-right">

					<div class="relative text-sm  invisible lg:visible">

						<a href="{{ route('genes')}}" class="no-underline hover:underline text-gray-800 p-2">
						<i class="fas fa-dna text-gray-400"></i> 	Genes
						</a>
					{{-- <a href="{{ route('diseases')}}" class="no-underline hover:underline text-gray-800 p-2">
							<i class="far fa-disease text-gray-400"></i> Diseases
						</a> --}}
						<a href="{{ route('submitters')}}" class="no-underline hover:underline text-gray-800 p-2">
							<i class="far fa-building text-gray-400"></i> Submitters
						</a>

						<a href="{{ route('statistics')}}" class="no-underline hover:underline text-gray-800 p-2">
							<i class="fas fa-chart-bar"></i> Statistics
						</a>

						<a href="{{ route('download')}}" class="no-underline hover:underline text-gray-800 p-2">
							<i class="fas fa-file-code"></i> Download
						</a>
						<a href="https://thegencc.org/faq" target="_blank" class="no-underline hover:underline text-gray-800 p-2">
							<i class="far fa-question-circle"></i> FAQ
						</a>
						<a href="https://thegencc.org/content/" target="_blank" class="no-underline hover:underline text-gray-800 p-2">
							<i class="far fa-comment"></i> Contact
						</a>
						<a href="https://creationproject.us7.list-manage.com/subscribe/post?u=47520fb4e4a2c9edfc44a61af&id=7ccf9c9b09" target="_blank" class="no-underline hover:underline text-blue-800 bg-blue-100 rounded-full text-xs py-1 px-2 leading-tight border-2 border-blue-800 ">
							<i class="fas fa-mail-bulk"></i> Stay Informed
						</a>

						{{-- <a href="{{ route('statistics')}}" class="no-underline hover:underline text-gray-800 p-2">
							<i class="far fa-info-circle"></i> --}}
							{{-- FAQs --}}
						{{-- </a>
						<a href="{{ route('statistics')}}" class="no-underline hover:underline text-gray-800 p-3">
							<i class="fas fa-chart-bar"></i> About
						</a> --}}
						{{-- <a href="{{ route('statistics')}}" class="no-underline hover:underline text-gray-800 p-2">
							<i class="fas fa-paper-plane"></i>
						</a> --}}
						{{-- <a href="{{ route('statistics')}}" class="no-underline hover:underline text-gray-800 p-2">
							<i class="fas fa-link"></i>
						</a> --}}

							@guest
									{{-- @if (Route::has('register'))
											<a class="no-underline hover:underline text-gray-800  p-3" href="{{ route('register') }}"><i class="far fa-user-circle"></i> {{ __('Register') }}</a>
									@endif --}}
									{{-- <a class="no-underline hover:underline text-gray-800 p-2" href="{{ route('login') }}"><i class="fas fa-lock"></i></a> --}}
							@else

						{{-- <a href="{{ route('dashboard')}}" class="no-underline hover:underline text-gray-800 p-3">
							<i class="fas fa-cloud-upload-alt"></i>
						</a> --}}
									<a href="{{ route('dashboard')}}" class="no-underline hover:underline text-gray-800 p-3"><i class="fas fa-user-circle"></i>
										{{-- {{ Auth::user()->name }}'s Dashboard --}}
									</a>

									<a href="{{ route('logout') }}"
											class="no-underline hover:underline text-gray-800"
											onclick="event.preventDefault();
													document.getElementById('logout-form').submit();"><i class="fas fa-sign-out-alt"></i>
													{{-- {{ __('Logout') }} --}}
												</a>
									<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
											{{ csrf_field() }}
									</form>
							@endguest
					</div>


					<div class="block lg:hidden pr-4"  x-data="{ open: false }">
						<div  @click="open = true">
						<button id="nav-toggle" class="flex items-center px-3 py-2 border rounded text-gray-500 border-gray-600 hover:text-gray-900 hover:border-teal-500 appearance-none focus:outline-none">
							<svg class="fill-current h-3 w-3" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
								<title>Menu</title>
								<path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z" />
							</svg>
						</button>
						</div>
						<div x-show="open" @click.away="open = false" class="z-10 origin-top-right absolute right-0 mt-2 rounded-md shadow-lg" style="display: none;">
                <div class="rounded-md bg-white shadow-xs">
                	<div class="py-2" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">

										<a href="{{ route('genes')}}" class="whitespace-no-wrap block px-4 py-2 text-sm leading-5 text-gray-700">
											<i class="fas fa-dna text-gray-400"></i> 	Genes
											</a>
										{{-- <a href="{{ route('diseases')}}" class="no-underline hover:underline text-gray-800 p-2">
												<i class="far fa-disease text-gray-400"></i> Diseases
											</a> --}}
											<a href="{{ route('submitters')}}" class="whitespace-no-wrap block px-4 py-2 text-sm leading-5 text-gray-700">
												<i class="far fa-building text-gray-400"></i> Submitters
											</a>

											<a href="{{ route('statistics')}}" class="whitespace-no-wrap block px-4 py-2 text-sm leading-5 text-gray-700">
												<i class="fas fa-chart-bar"></i> Statistics
											</a>
											<a href="{{ route('download')}}" class="whitespace-no-wrap block px-4 py-2 text-sm leading-5 text-gray-700">
												<i class="fas fa-file-code"></i> Download
											</a>

											<a href="https://thegencc.org/faq" target="_blank" class="whitespace-no-wrap block px-4 py-2 text-sm leading-5 text-gray-700">
												<i class="far fa-question-circle"></i> FAQ
											</a>
											<a href="https://thegencc.org/content/" target="_blank" class="whitespace-no-wrap block px-4 py-2 text-sm leading-5 text-gray-700">
												<i class="far fa-comment"></i> Contact
											</a>
											<a href="https://creationproject.us7.list-manage.com/subscribe/post?u=47520fb4e4a2c9edfc44a61af&id=7ccf9c9b09" target="_blank" class="whitespace-no-wrap block px-4 py-2 text-sm leading-5 text-gray-700">
												<i class="fas fa-mail-bulk"></i> Stay Informed
											</a>

                	</div>
                </div>
            </div>
					</div>
				</div>

			</div>


			{{-- <div class="w-full flex-grow lg:flex lg:items-center lg:w-auto hidden lg:block mt-2 lg:mt-0 bg-white z-20" id="nav-content">
				<ul class="list-reset lg:flex flex-1 items-center px-4 md:px-0">
					<li class="mr-6 my-2 md:my-0">
					<a href="{{ route('home')}}" class="text-sm block py-1 md:py-3 align-middle text-gray-500 no-underline hover:text-gray-900 border-b-2 border-white hover:border-pink-500 ">
							Home
						</a>
					</li>

					<li class="mr-6 my-2 md:my-0">
						<a href="{{ route('genes')}}" class="text-sm block py-1 md:py-3 align-middle text-gray-500 no-underline hover:text-gray-900 border-b-2 border-white hover:border-pink-500">
							Genes</span>
						</a>
					</li>
					<li class="mr-6 my-2 md:my-0">
					<a href="{{ route('diseases')}}" class="text-sm block py-1 md:py-3 align-middle text-gray-500 no-underline hover:text-gray-900 border-b-2 border-white hover:border-pink-500">
							Diseases</span>
						</a>
					</li>
					<li class="mr-6 my-2 md:my-0">
					<a href="{{ route('submissions')}}" class="text-sm block py-1 md:py-3 align-middle text-gray-500 no-underline hover:text-gray-900 border-b-2 border-white hover:border-pink-500">
							Submissions</span>
						</a>
					</li>
					<li class="mr-6 my-2 md:my-0">
					<a href="{{ route('statistics')}}" class="text-sm block py-1 md:py-3 align-middle text-gray-500 no-underline hover:text-gray-900 border-b-2 border-white hover:border-pink-500">
							Statistics</span>
						</a>
					</li>
					@auth
					<li class="mr-6 my-2 md:my-0">
						<a href="{{ route('importers')}}" class="text-sm block py-1 md:py-3 align-middle text-gray-500 no-underline hover:text-gray-900 border-b-2 border-white hover:border-pink-500">
							Importers</span>
						</a>
					</li>
					@endauth

				</ul>

				<div class="relative pull-right pl-4 pr-4 md:pr-0">
					<input type="search" placeholder="Search" class="w-full bg-gray-100 text-sm text-gray-600 transition border focus:outline-none focus:border-gray-700 rounded py-1 px-2 pl-10 appearance-none leading-normal">
					<div class="absolute search-icon" style="top: 0.375rem;left: 1.75rem;">
						<svg class="fill-current pointer-events-none text-gray-800 w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
							<path d="M12.9 14.32a8 8 0 1 1 1.41-1.41l5.35 5.33-1.42 1.42-5.33-5.34zM8 14A6 6 0 1 0 8 2a6 6 0 0 0 0 12z"></path>
						</svg>
					</div>
				</div>

			</div> --}}

		</div>
	</nav>