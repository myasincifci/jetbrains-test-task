### A Pluto.jl notebook ###
# v0.16.0

using Markdown
using InteractiveUtils

# This Pluto notebook uses @bind for interactivity. When running this notebook outside of Pluto, the following 'mock version' of @bind gives bound variables a default value (instead of an error).
macro bind(def, element)
    quote
        local el = $(esc(element))
        global $(esc(def)) = Core.applicable(Base.get, el) ? Base.get(el) : missing
        el
    end
end

# ╔═╡ cf82077a-81c2-11eb-1de2-09ed6c35d810
begin
	import ImageMagick
	using PlutoUI
	using Colors, ColorSchemes, Images
	using Plots
	using LaTeXStrings
	
	using Statistics, LinearAlgebra  # standard libraries
end

# ╔═╡ 4c1ebac8-81b7-11eb-19fa-f704b4d84a21
html"""
<div style="
position: absolute;
width: calc(100% - 30px);
border: 50vw solid #282936;
border-top: 500px solid #282936;
border-bottom: none;
box-sizing: content-box;
left: calc(-50vw + 15px);
top: -500px;
height: 500px;
pointer-events: none;
"></div>

<div style="
height: 500px;
width: 100%;
background: #282936;
color: #fff;
padding-top: 68px;
">
<span style="
font-family: Vollkorn, serif;
font-weight: 700;
font-feature-settings: 'lnum', 'pnum';
"> <p style="
font-size: 1.5rem;
opacity: .8;
"><em>Section 2.1</em></p>
<p style="text-align: center; font-size: 2rem;">
<em> Principal Component Analysis </em>
</p>

<p style="
font-size: 1.5rem;
text-align: center;
opacity: .8;
"><em>Lecture Video</em></p>
<div style="display: flex; justify-content: center;">
<div  notthestyle="position: relative; right: 0; top: 0; z-index: 300;">
<iframe src="https://www.youtube.com/embed/iuKrM_NzxCk" width=400 height=250  frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>
</div>
</div>

<style>
body {
overflow-x: hidden;
}
</style>"""

# ╔═╡ c593a748-81b6-11eb-295a-a9800f9dec6d
PlutoUI.TableOfContents(aside=true)

# ╔═╡ deb2af50-8524-11eb-0dd4-9d799ff6d3e2
md"""
# Introduction: Understanding data

In this notebook we will start looking at more general kinds of **data**, not only images, and we'll try to extract some information from the image using statistical methods, namely [**principal component analysis**](https://en.wikipedia.org/wiki/Principal_component_analysis).  

This method tries to answer the questions "which 'directions' are the most important  in the data" and "can we [reduce the dimensionality](https://en.wikipedia.org/wiki/Dimensionality_reduction) (number of useful variables) of the data"? 

This can be viewed as another take on the question of finding and exploiting **structure** in data. It also leads towards ideas of **machine learning**.
"""

# ╔═╡ 2e50a070-853f-11eb-2045-b1cc43c29768
md"""
# Rank of a matrix
"""

# ╔═╡ ed7ff6b2-f863-11ea-1a59-eb242a8674e3
md"## Flags"

# ╔═╡ fed5845e-f863-11ea-2f95-c331d3c62647
md"Let's start off by recalling ideas about  **multiplication tables** or **outer products**:"

# ╔═╡ 0e1a6d80-f864-11ea-074a-5f7890180114
outer(v, w) = [x * y for x in v, y in w]

# ╔═╡ 2e497e30-f895-11ea-09f1-d7f2c1f61193
outer(1:10, 1:12)

# ╔═╡ cfdd04f8-815a-11eb-0409-79a2599c29ab
md"""
Each column is a multiple (in general not an integer multiple) of any other column; and each row is a multiple of any other row.

This is an example of a **structured matrix**, i.e. one in which we need to store *less information* to store the matrix than the full $(m \times n)$ table of numbers (even though no element is 0). For example, for an outer product we only need $m + n$ numbers, which is usually *much* less.
"""

# ╔═╡ ab3d55cc-f905-11ea-2f22-5398f3aca803
md"Some flags give simple examples of outer products, for example"

# ╔═╡ 13b6c108-f864-11ea-2447-2b0741f15c7b
flag = outer([1, 0.1, 2], ones(6))

# ╔═╡ e66b30a6-f914-11ea-2c0f-35282d45a30a
ones(6)

# ╔═╡ 71d1b12e-f895-11ea-39df-f5c18a7766c3
flag2 = outer([1, 0.1, 2], [1, 1, 1, 3, 3, 3])

# ╔═╡ 356267fa-815b-11eb-1c57-ad14fd6e91a7
md"""
Note that outer products are not always immediate to recognise just by looking at an image! But you should be able to recognise that there is some kind of structure.
"""

# ╔═╡ cdbe1d8e-f905-11ea-3884-efeeef386dda
md"## Matrix rank"

# ╔═╡ d9aa9af0-f865-11ea-379e-f16b452bd94c
md"""
If a matrix can be written exactly as a *single* multiplication table / outer product, we say that its **rank** is 1, and we call it a **rank-1** matrix. Similarly, if it can be written as the *sum* of *two* outer products, it has **rank 2**, etc.
"""

# ╔═╡ 2e8ae92a-f867-11ea-0219-1bdd9627c1ea
md"Let's see what a random rank-1 matrix looks like:"

# ╔═╡ 9ad13804-815c-11eb-0253-8f8baf15eee3
w = 300

# ╔═╡ 38adc490-f867-11ea-1de5-3b633aff7c97
image = outer([1; 0.4; rand(50)], rand(w));

# ╔═╡ 946fde3c-815b-11eb-3039-db4105bc43ab
md"""
It has a characteristic checkerboard or patchwork look.
"""

# ╔═╡ ab924210-815b-11eb-07fe-411db58fbc3a
md"""
Here's a random rank-2 matrix:
"""

# ╔═╡ b5094384-815b-11eb-06fd-1f40134c6fd8
md"""
We see that it starts to look less regular.
"""

# ╔═╡ cc4f3fee-815b-11eb-2982-9b797b806b45
md"""
#### Exercise: 

Make an interactive visualisation of a random rank-$n$ matrix where you can vary $n$.
"""

# ╔═╡ dc55775a-815b-11eb-15b7-7993190bffab
md"""
## Effect of noise
"""

# ╔═╡ 9cf23f9a-f864-11ea-3a08-af448aceefd8
md"""
Now what happens if we add a bit of **noise**, i.e. randomness, to a rank-1 matrix?
"""

# ╔═╡ a5b62530-f864-11ea-21e8-71ccfed487f8
noisy_image = image .+ 0.03 .* randn.();

# ╔═╡ c41df86c-f865-11ea-1253-4942bbdbe9d2
md"""The noisy image now has a rank larger than 1. But visually we can see that it is "close to" the original rank-1 matrix. 

Given this matrix, how could we discover that it is close to a structured, rank-1 matrix? We would like to be able to find this out and say that the matrix is close to a simple one."""

# ╔═╡ 7fca33ac-f864-11ea-2a8b-933eb382c172
md"## Images as data"

# ╔═╡ 283f5da4-f866-11ea-27d4-957ca2551b92
md"""

Now let's treat the image as a **data matrix**, so that each column of the image / matrix is a **vector** representing one observation of data. (In data science it is often the rows that correspond to observations.)

Let's try to visualize those vectors, taking just the first two rows of the image as the $x$ and $y$ coordinates of our data points:
"""

# ╔═╡ 54977286-f908-11ea-166d-d1df33f38454
image[1:2, 1:20]

# ╔═╡ 7b4e90b4-f866-11ea-26b3-95efde6c650b
begin 
	xx = image[1, :]
	yy = image[2, :]
end

# ╔═╡ f574ad7c-f866-11ea-0efa-d9d0602aa63b
md"# From images to data"

# ╔═╡ 8775b3fe-f866-11ea-3e6f-9732e39a3525
md"""
We would like to **visualise** this data with the `Plots.jl` package, passing it the $x$ and $y$ coordinates as arguments and plotting a point at each $(x_i, y_i)$ pair.
We obtain the following plot of the original rank-1 matrix and the noisy version:
"""

# ╔═╡ cb7e85e4-815c-11eb-3923-7b9537801bae
gr()

# ╔═╡ 1147cbda-f867-11ea-08fa-ef6ed2ae1e93
begin
	xs = noisy_image[1, :]
	ys = noisy_image[2, :]
	
	scatter(xs, ys, label="noisy", m=:., alpha=0.3, ms=4, ratio=1)

	scatter!(xx, yy, 
			leg=:topleft, label="rank-1", ms=3, alpha=0.3, 
			size=(500, 400), m=:square, c=:red,
			framestyle=:origin)

	
	
	
	title!("Plotting a rank-1 matrix gives a straight line!")
end

# ╔═╡ 8a611e36-f867-11ea-121f-317b7c145fe3
md"We see that the exact rank-1 matrix has columns that **lie along a line** through the origin in this representation, since they are just multiples of one another.

E.g. If $(x_1, y_1)$ and $(x_2, y_2)$ are two columns with $x_2 = cx_1$ and $y_2 = cy_1$, then $y_2 / x_2 = y_1 / x_1$, so they lie along the same line through the origin.

The approximate rank-1 matrix has columns that **lie *close to* the line**!"

# ╔═╡ f7371934-f867-11ea-3b53-d1566684585c
md"So, given the data, we want to look at it do see if it lies close to a line or not.
How can we do so in an *automatic* way?
"

# ╔═╡ 987c1f2e-f868-11ea-1125-0d8c02843ae4
md"""
## Measuring data cloud "size" -- using statistics
"""

# ╔═╡ 9e78b048-f868-11ea-192e-d903265d1eb5
md"Looking at this cloud of data points, a natural thing to do is to try to *measure* it: How wide is it, and how tall?"

# ╔═╡ 24df1f32-ec90-11ea-1f6d-03c1bfa5df8e
md"""For example, let's think about calculating the width of the cloud, i.e. the range of possible $x$-values of the data. For this purpose the $y$-values are actually irrelevant.
"""

# ╔═╡ b264f724-81bf-11eb-1052-295b81cde5fb
md"""
A natural idea would be to just scan the data and take the maximum and minimum values. However, real data often contains anomalously large values called **outliers**, which would dramatically affect this calculation. Instead we need to use a **statistical** method where we weight the data and average over all the data points. This process will hopefully be affected less by outliers, and will give a more representative idea of the size of the **bulk** of the data.
"""

# ╔═╡ 09991230-8526-11eb-04aa-fb904bd2036c
md"""
A first step in analysing data is often to **centre** it data around 0 by subtracting the mean, sometimes called "de-meaning":
"""

# ╔═╡ aec46a9b-f743-4cbd-97a7-3ef3cac78b12
begin
	xs_centered = xs .- mean(xs)
	ys_centered = ys .- mean(ys)
end

# ╔═╡ 1b8c743e-ec90-11ea-10aa-e3b94f768f82
scatter(xs_centered, ys_centered, ms=5, alpha=0.5, ratio=1, leg=false, framestyle=:origin)

# ╔═╡ eb867f18-852e-11eb-005f-e15b6d0d0d95
md"""
## Measuring a "width" of a data set
"""

# ╔═╡ f7079016-852e-11eb-3bc9-53fa0846276f
md"""
A natural way to measure the width of a data set could be to measure some kind of width *separately* in both the $x$ and $y$ directions, in other words by **projecting** the data onto one of the axes, while ignoring the other one. Let's start with the $x$ coordinates of the centred data and try to average them.
	
If we literally average them we will get $0$ (since we have subtracted the mean). We could ask "on average how far away from the origin is the data". This would give the [mean absolute deviation](https://en.wikipedia.org/wiki/Average_absolute_deviation):
"""

# ╔═╡ 870d3efa-f8fc-11ea-1593-1552511dcf86
begin
	scatter(xs_centered, ys_centered, ms=5, alpha=0.5, ratio=1, leg=false, framestyle=:origin)

	scatter!(xs_centered, zeros(size(xs_centered)), ms=5, alpha=0.1, ratio=1, leg=false, framestyle=:origin)

	for i in 1:length(xs_centered)
		plot!([(xs_centered[i], ys_centered[i]), (xs_centered[i], 0)], ls=:dash, c=:black, alpha=0.1)
	end

	plot!()
	
end

# ╔═╡ 4fb82f18-852f-11eb-278d-cf93571f4adc
mean(abs.(xs_centered))

# ╔═╡ 5fcf832c-852f-11eb-1354-792933a891a5
md"""
This is a perfectly good computational measure of distance. However, there is another measure which is easier to reason about theoretically / analytically:
"""

# ╔═╡ ef4a2a54-81bf-11eb-358b-0da2072f20c8
md"""
### Root-mean-square distance: Standard deviation
"""

# ╔═╡ f5358ce4-f86a-11ea-2989-b1f37be89183
md"""
The **standard deviation** is the **root-mean-square** distance of the centered data from the origin. 

In other words, we first *square* the distances (or displacements) from the origin, then take the mean of those, giving the **variance**. However, since we have squared the original distances, this gives a quantity with units "distance$^2$", so we need to take the square root to get back to a measurable length:
"""

# ╔═╡ 2c3721da-f86b-11ea-36cf-3fe4c6622dc6
begin 
	σ_x = √(mean(xs_centered.^2))   # root-mean-square distance from 0
	σ_y = √(mean(ys_centered.^2))
end

# ╔═╡ 03ab44c0-f8fd-11ea-2243-1f3580f98a65
md"This gives the following approximate extents (standard deviations) of the cloud:"

# ╔═╡ 6dec0db8-ec93-11ea-24ad-e17870ee64c2
begin
	scatter(xs_centered, ys_centered, ms=5, alpha=0.5, ratio=1, leg=false, 
			framestyle=:origin)

	vline!([-2*σ_x, 2*σ_x], ls=:dash, lw=2, c=:green)
	hline!([-2*σ_y, 2*σ_y], ls=:dash, lw=2, c=:blue)
	
	annotate!( 2σ_x * 0.93, 0.03, text(L"2\sigma_x",  14, :green))
	annotate!(-2σ_x * 0.88, 0.03, text(L"-2\sigma_x", 14, :green))
	
	annotate!(0.05,  2σ_y * 1.13, text(L"2\sigma_y",  14, :blue))
	annotate!(0.06, -2σ_y * 1.14, text(L"-2\sigma_y", 14, :blue))

end

# ╔═╡ 5fab2c32-f86b-11ea-2f27-ed5feaac1fa5
md"""
We expect most (around 95%) of the data to be contained within the interval $\mu \pm 2 \sigma$, where $\mu$ is the mean and $\sigma$ is the standard deviation. (This assumes that the data is **normally distributed**, which is not actually the case for the data generated above.)
"""

# ╔═╡ ae9a2900-ec93-11ea-1ae5-0748221328fc
md"## Correlated data"

# ╔═╡ b81c9db2-ec93-11ea-0dbd-4bd0951cb2cc
md"""
However, from the figure it is clear that $x$ and $y$ are not the "correct directions" to use for this data set. It would be more natural to think about other directions: the direction in which the data set is mainly pointing (roughly, the direction in which it's longest), together with the approximately perpendicular direction in which it is  narrowest.

We need to find *from the data* which directions these are, and the extent (width) of the data cloud in those directions.

However, we cannot obtain any information about those directions by looking *separately* at $x$-coordinates and $y$-coordinates, since within the same bounding boxes that we just calculated the data can be distributed in many different ways.

Rather, the information that we need is encoded in the *relationship* between the values of $x_i$ and $y_i$ *for the points in the data set*.

For our data set, when $x$ is large and negative, $y$ is also rather negative; when $x$ is 0, $y$ is near $0$, and when $x$ is large and positive, so is $y$. We say that $x$ and $y$ are **correlated** -- literally they are mutually ("co") related, such that knowing some information about one of them allows us to predict something about the other. 

For example, if I measure a new data point from the same process and I find that $x$ is around $0.25$ then I would expect $y$ to be within the range $0.05$ to $0.2$, and it would be very surprising if $y$ were -0.5.

"""

# ╔═╡ 80722856-f86d-11ea-363d-53fc5f6b8152
md"""
Although there are standard methods to calculate this correlation, we prefer to hone our **intuition** using computational thinking instead!
"""

# ╔═╡ b8fa6a1c-f86d-11ea-3d6b-2959d737254b
md"""
We want to think about different *directions*, so let's introduce an angle $\theta$ to describe the direction along which we are looking. We want to calculate the width of the cloud *along that direction*.

Effectively we are *changing coordinates* to a new coordinate, oriented along the line. To do this  requires more linear algebra than we are assuming in this course, but let's see what it looks like:
"""

# ╔═╡ 3547f296-f86f-11ea-1698-53d3c1a0bc30
md"## Rotating the axes"

# ╔═╡ 7a83101e-f871-11ea-1d87-4946162777b5
md"""By rotating the axes we can "look in different directions" and calculate the width of the data set "along that direction". What we are really doing is a perpendicular **projection** of the data onto that direction."""

# ╔═╡ e8276b4e-f86f-11ea-38be-218a72452b10
M = [xs_centered ys_centered]'

# ╔═╡ 1f373bd0-853f-11eb-0f8e-19cb7f376182
eigvals(cov(M')) .* 199

# ╔═╡ d71fdaea-f86f-11ea-1a1f-45e4d50926d3
imax = argmax(M[1, :])

# ╔═╡ 757c6808-f8fe-11ea-39bb-47e4da65113a
svdvals(M)

# ╔═╡ 1232e848-8540-11eb-089b-2185cc06f23a
M

# ╔═╡ 7cb04c9a-8358-11eb-1255-8d8c90916c37
gr()

# ╔═╡ cd9e05ee-f86f-11ea-0422-25f8329c7ef2
R(θ)= [cos(θ) sin(θ)
	  -sin(θ) cos(θ)]

# ╔═╡ 7eb51908-f906-11ea-19d2-e947d81cb743
md"In the following figure, we are rotating the axis (red arrow) around in the left panel. In the right panel we are viewing the data from the point of view of that new coordinate direction, in other words projecting onto that direction, effectively as if we rotated our head so the red vector was horizontal:"

# ╔═╡ 4f1980ea-f86f-11ea-3df2-35cca6c961f3
md"""
degrees = $(@bind degrees Slider(0:360, default=28, show_value=true)) 
"""

# ╔═╡ c9da6e64-8540-11eb-3984-47fdf8be0dac
md"""
## Rotating the data
"""

# ╔═╡ f70065aa-835a-11eb-00cb-ffa27bcb486e
θ = π * degrees / 180   # radians

# ╔═╡ 3b71142c-f86f-11ea-0d43-47011d00786c
p1 = begin
	
	scatter(M[1, :], M[2, :], ratio=1, leg=false, ms=2.5, alpha=0.5,
			framestyle=:origin)
	
	projected = ([cos(θ) sin(θ)] * M) .* [cos(θ) sin(θ)]'
	scatter!(projected[1, :], projected[2, :], m=:3, alpha=0.1, c=:green)
	
	
	lines_x = reduce(vcat, [M[1, i], projected[1, i], NaN] for i in 1:size(M, 2))
	lines_y = reduce(vcat, [M[2, i], projected[2, i], NaN] for i in 1:size(M, 2))
	
	
# 	for i in 1:size(M, 2)
# 		plot!([M[1, i], projected[1, i]], [M[2, i], projected[2, i]], ls=:dash, c=:black, alpha=0.1)
# 	end
	
	plot!(lines_x, lines_y, ls=:dash, c=:black, alpha=0.1)
	
	
	plot!([0.7 .* (-cos(θ), -sin(θ)), 0.7 .* (cos(θ), sin(θ))], lw=1, arrow=true, c=:red, alpha=0.3)
	xlims!(-0.7, 0.7)
	ylims!(-0.7, 0.7)
	
	scatter!([M[1, imax]], [M[2, imax]],  ms=3, alpha=1, c=:yellow)

	annotate!(0, 1.2, text("align arrow with cloud", :red, 10))
end;

# ╔═╡ 8b8e6b2e-8531-11eb-1ea6-637db25b28d5
p1

# ╔═╡ 88bbe1bc-f86f-11ea-3b6b-29175ddbea04
p2 = begin
	M2 = R(θ) * M
	
	scatter(M2[1, :], M2[2, :],ratio=1, leg=false, ms=2.5, alpha=0.3, framestyle=:origin, size=(500, 500))
	
	# plot!([(-0.6, 0), (0.6, 0)], lw=3, arrow=true, c=:red, xaxis=false, yaxis=false, xticks=[], yticks=[])
	
	# scatter!([M2[1, imax]], [M2[2, imax]], ms=3, alpha=1, c=:yellow)

	xlims!(-0.7, 0.7)
	ylims!(-0.7, 0.7)
	
	scatter!(M2[1, :], zeros(size(xs_centered)), ms=3, alpha=0.1, ratio=1, leg=false, framestyle=:origin, c=:green)

	
	lines2_x = reduce(vcat, [M2[1, i], M2[1, i], NaN] for i in 1:size(M2, 2))
	lines2_y = reduce(vcat, [M2[2, i], 0, NaN] for i in 1:size(M2, 2))
	
	# for i in 1:size(M2, 2)
	# 	plot!([(M2[1, i], M2[2, i]), (M2[1, i], 0)], ls=:dash, c=:black, alpha=0.1)
	# end
	
	plot!(lines2_x, lines2_y, ls=:dash, c=:black, alpha=0.1)
	
	σ = std(M2[1, :])
	vline!([-2σ, 2σ], ls=:dash, lw=2)
	
# 	plot!(0.5 * [-cos(θ), cos(θ)], 0.5 * [sin(θ), -sin(θ)], c=:black, alpha=0.5, lw=1, arrow=true)
# 	plot!(0.5 * [-sin(θ), sin(θ)], 0.5 * [-cos(θ), cos(θ)], c=:black, alpha=0.5, lw=1, arrow=true)
	
# 	plot!(0.5 * [cos(θ), -cos(θ)], 0.5 * [-sin(θ), sin(θ)], c=:black, alpha=0.5, lw=1, arrow=true)
# 	plot!(0.5 * [sin(θ), -sin(θ)], 0.5 * [cos(θ), -cos(θ)], c=:black, alpha=0.5, lw=1, arrow=true)
	
	title!("σ = $(round(σ, digits=4))")
	
	annotate!(2σ+0.05, 0.05, text("2σ", 10, :green))
	annotate!(-2σ-0.05, 0.05, text("-2σ", 10, :green))

end;

# ╔═╡ 2ffe7ed0-f870-11ea-06aa-390581500ca1
plot(p2)

# ╔═╡ a5cdad52-f906-11ea-0486-755a6403a367
md"""
We see that the extent of the data in the direction $\theta$ varies as $\theta$ changes. Let's plot the variance in direction $\theta$ as a function of $\theta$:
"""

# ╔═╡ 0115c974-f871-11ea-1204-054510848849
begin
	variance(θ) = var( (R(θ) * M)[1, :] )
	variance(θ::AbstractArray) = variance(θ[1])
end

# ╔═╡ 0935c870-f871-11ea-2a0b-b1b824379350
p3 = begin 
	plot(0:360, variance.(range(0, 2π, length=361)), leg=false, size=(400, 200))
	scatter!([degrees], [σ^2])
	xlabel!("θ")
	ylabel!("variance in direction θ")
end

# ╔═╡ 6646abe0-835b-11eb-328a-55ca22f89c7d
σs = svdvals(M)

# ╔═╡ 31e4b138-84e8-11eb-36a8-8b90746fbb0f
variances = σs.^2 ./ 199

# ╔═╡ ef850e8e-84e7-11eb-1cb0-870c3000841d
1 ./ σs

# ╔═╡ 031a2894-84e8-11eb-1381-9b9e86f2fa0a
M

# ╔═╡ e4af4d26-f877-11ea-1de3-a9f8d389138e
md"""The direction in which the variance is **maximised** gives the most important direction: It is the direction along which the data "points", or the direction which best distinguishes different data points. This is often called the first **principal component** in statistics, or the first **singular vector** in linear algebra. 

We can also now quantify *how close* the data is to lying along that single line, using the width in the *perpendicular* direction: if that width is "much smaller" than  the width in the first principal direction then the data is close to being rank 1.
"""

# ╔═╡ bf57f674-f906-11ea-08eb-9b50818a025b
md"The simplest way to maximise this function is to evaluate it everywhere and find one of the places where it takes the maximum value:"

# ╔═╡ 17e015fe-f8ff-11ea-17b4-a3aa072cd7b3
begin
	θs = 0:0.01:2π
	fs = variance.(θs)

	θmax = θs[argmax(fs)]
	θmin = θs[argmin(fs)]

	fmax = variance(θmax)
	fmin = variance(θmin)
end

# ╔═╡ 045b9b98-f8ff-11ea-0d49-5b209319e951
begin
	scatter(xs_centered, ys_centered, ms=5, alpha=0.3, ratio=1, leg=false, 
			framestyle=:origin)

	plot!([(0, 0), 2*sqrt(fmax) .* (cos(θmax), sin(θmax))], arrow=true, lw=3, c=:red)
	plot!([(0, 0), 2*sqrt(fmin) .* (cos(θmin), sin(θmin))], arrow=true, lw=3, c=:red)

end

# ╔═╡ cfec1ec4-f8ff-11ea-265d-ab4844f0f739
md"""
Note that the directions that maximise and minimise variance are perpendicular. This is always the case, as shown using the Singular-Value Decomposition (SVD) in linear algebra.

There are different ways to think about this procedure. We can think of it as effectively "fitting an ellipse" to the data, where the widths of the ellipse axes show the relative importance of each direction in the data.

Alternatively we can think of it as fitting a multivariate normal distribution by finding a suitable covariance matrix.
"""

# ╔═╡ e6e900b8-f904-11ea-2a0d-953b99785553
begin
	circle = [cos.(θs) sin.(θs)]'
	stretch = [2 * sqrt(fmax) 0
				0   		2 * sqrt(fmin)]
	ellipse = R(-θmax) * stretch * circle 
	
	plot!(ellipse[1, :], ellipse[2, :], series=:shape, alpha=0.4, fill=true, c=:orange)
end

# ╔═╡ 301f4d06-8162-11eb-1cd6-31dd8da164b6
md"""
Note also that an ellipse is the image of the unit circle under a *linear* transformation. We are effectively learning what the best linear transformation is that transforms the unit circle into our data cloud.
"""

# ╔═╡ aaff88e8-f877-11ea-1527-ff4d3db663db
md"## Higher dimensions"

# ╔═╡ aefa84de-f877-11ea-3e26-678008e9739e
md"""
Can we generalise this to dimensions higher than 2? 
Let's think about 3D. If we take columns of the first *three* rows of the original image, we have vectors in 3D.

If we plot these vectors in 3D, a rank-1 matrix will give a straight line in 3D, while a rank-2 matrix will give a **plane** in 3D. Rank-2 + noise gives a noisy cloud lying close to a plane.

Similarly to what we did above, we need to calculate the ellipsoid that best fits the data. The widths of the axes of the ellipsoid tell us how close to being a line or a plane (rank-1 or rank-2) the data is.
"""

# ╔═╡ 0bd9358e-f879-11ea-2c83-ed4e7bf9d903
md"In more than 3D we can no longer visualise the data, but the same idea applies. The calculations are done using the SVD.

If the widths of the ellipsoid in some directions are very small, we can ignore those directions and hence reduce the dimensionality of the data, by changing coordinates to the principal components."

# ╔═╡ 9960e1c2-85a0-11eb-3d68-cd3a07a9c0b5
md"""
## What is the Singular-Value Decomposition (SVD)?
"""

# ╔═╡ dc4cca88-85a0-11eb-2791-d7610a610e36
md"""
The **Singular-Value Decomposition (SVD)** is a way of writing any matrix in terms of simpler matrices. Thinking in terms of linear transformations, *any* linear transformation $T$ has the same effect as a sequence of three simple transformations:

T = rotation₂ ∘ stretch ∘ rotation₁

In terms of matrices, for *any* matrix $M$ it is possible to write

$$M = U \, \Sigma \, V^\text{T}$$

where $U$ and $V$ are **orthogonal** matrices, i.e. they satisfy $U U^\text{T} = I$ and $V V^\text{T} = I$, and $\Sigma$ is a diagonal matrix. 

Orthogonal matrices have determinant $\pm 1$, so they leave areas unchanged. They are rotations, possibly combined with reflections.

There are algorithms from numerical linear algebra to calculate this decomposition. In Julia we call the `svd` function, e.g.
"""

# ╔═╡ 453689c2-85a2-11eb-2cbc-7d6476b42f2f
let
	M = [2 1
		 1 1]
	
	svd(M)
end

# ╔═╡ 91b77acc-85a2-11eb-19bb-bd2bdd0c1a68
md"""
Let's look at the action of the matrix on the unit disc. To generate points in the unit disc we generate points in $[-1, 1]^2$ and *reject* those lying outside:
"""

# ╔═╡ f92c75f6-85a3-11eb-1689-23aeaa3daeb7
begin
	unit_disc = [ (-1.0 .+ 2.0 .* rand(2)) for i in 1:2000 ]
	unit_disc = reduce(hcat, [x for x in unit_disc if x[1]^2 + x[2]^2 < 1])
end

# ╔═╡ 03069da6-85a4-11eb-2ac5-87b767846550
scatter(unit_disc[1, :], unit_disc[2, :], ratio=1, leg=false, alpha=0.5, ms=3)

# ╔═╡ 1647a126-85a4-11eb-3923-5f5a6f703403
md"""
t = $(@bind tt Slider(0:0.01:1, show_value=true))
"""

# ╔═╡ 40b87cbe-85a4-11eb-30f8-cf7b5e79c19a
pp1 = begin
	scatter(unit_disc[1, :], unit_disc[2, :], ratio=1, leg=false, alpha=0.5, title="stretch + rotate")
	result =  [1 + tt  tt; tt  1] * unit_disc
	scatter!(result[1, :], result[2, :], alpha=0.2)

	ylims!(-3, 3)
	xlims!(-3, 3)
end;

# ╔═╡ 28a7d6dc-85a5-11eb-0e4b-b7e9b4c592ed
pp2 = begin
	UU, Sigma, VV = svd([1 + tt  tt; tt  1])
	scatter(unit_disc[1, :], unit_disc[2, :], ratio=1, leg=false, alpha=0.5, title="stretch")
	
	result2 = Diagonal(Sigma) * unit_disc
	
	scatter!(result2[1, :], result2[2, :], alpha=0.2)
	
	ylims!(-3, 3)
	xlims!(-3, 3)

end;


# ╔═╡ 6ec7f980-85a5-11eb-12fc-cb132db28d83
plot(pp2, pp1)

# ╔═╡ 92a2827e-84e9-11eb-1e85-6f49b1da7277
md"""
## Rotations in 300 dimensions
"""

# ╔═╡ e84adec2-84e8-11eb-2157-dd491588ccf0
md"""
We have been thinking of 300 points in 2 dimensions. Instead, we could think about 2 points **in 300 dimensions**. In some sense, this is what 
"transpose really does"!
"""

# ╔═╡ 571b88ac-85a6-11eb-2887-a368a19bce4d
md"""
The $V$ in the SVD is another rotation that we "cannot see" in the above pictures. In our case it is a rotation in 300 dimensions! But in fact we can visualise it as follows, where we multiply our data by a $300 \times 300$ orthogonal matrix!

We first take a random *anti-symmetric* matrix, i.e. one for which $M = -M^\text{t}$.
We then turn that into an orthogonal matrix by taking the so-called **matrix exponential**.
"""

# ╔═╡ 12010a58-84eb-11eb-106f-cb4e3e0c879b
begin
	dim = size(M, 2)
	anti_symmetric = randn(dim, dim)
	anti_symmetric -= anti_symmetric'
end

# ╔═╡ 696d2768-84eb-11eb-39e0-612e074a2c27
@bind t Slider(0:0.0002:1, show_value=true, default=0.0)

# ╔═╡ 7b7b5128-84eb-11eb-3974-9b4c08fab8bb
begin
	M_rotated = M * exp(t * anti_symmetric)
	scatter(M_rotated[1, :], M_rotated[2, :], leg=false, alpha=0.5, )
	scatter!(M[1, :], M[2, :], alpha=0.5)
	
	ylims!(-0.3, 0.3)
	xlims!(-0.6, 0.6)
end

# ╔═╡ 805b9616-85a7-11eb-22e8-db8ee67071ae
md"""
We see that the data rotates around in 300 dimensions, but always is projected to the *same* ellipse.
"""

# ╔═╡ 90656ce6-84fb-11eb-1aac-4bd7747613db
U, Σ, V = svd(M, full=true)

# ╔═╡ aec542a2-84fb-11eb-322c-27fc2c45f6ef
M18 = M * V 

# ╔═╡ b55dcfd2-84fb-11eb-1766-17dc8b7a17d0
scatter(M18[1, :], M18[2, :], alpha=0.5, leg=false, ratio=1, xlim=(-5, 5))


# ╔═╡ 1cf3e098-f864-11ea-3f3a-c53017b73490
md"#### Appendix"

# ╔═╡ 2917943c-f864-11ea-3ee6-db952ca7cd67
begin
	show_image(M) = get.(Ref(ColorSchemes.rainbow), M ./ maximum(M))
	show_image(x::AbstractVector) = show_image(x')
end

# ╔═╡ 43bff19e-f864-11ea-2315-0f85b532a325
show_image(flag)

# ╔═╡ 79d2c6f4-f895-11ea-30c4-9d1102c99482
show_image(flag2)

# ╔═╡ b183b6ca-f864-11ea-0b34-4dd3f4f5e69d
show_image(image)

# ╔═╡ 74c04322-815b-11eb-2308-7b3d571cf613
begin
	
	image2 = outer([1; 0.4; rand(50)], rand(w)) + 
	         outer(rand(52), rand(w))
	
	show_image(image2)
end

# ╔═╡ f6713bec-815b-11eb-2fc4-6b0326a64b16
show_image(image)

# ╔═╡ 5471ddce-f867-11ea-2519-21981f5ea68b
show_image(noisy_image)

# ╔═╡ 1957f71c-f8eb-11ea-0dcf-339bfa7f96fc
show_image(image[1:2, 1:20])

# ╔═╡ 72bb11b0-f88f-11ea-0e55-b1108300f854
loss(M1, M2) = sum( (M1[i] - M2[i])^2 for i in 1:length(M1) if !ismissing(M2[i]) )

# ╔═╡ feeeb24a-f88f-11ea-287f-219e53615f32
function split_up(v, m, n)
	return v[1:m], v[m+1:m+n], v[m+n+1:2m+n], v[2m+n+1:2m+2n]
end

# ╔═╡ 0bcc8852-f890-11ea-3715-11cbead7f636
function ff(v, m, n)
	v1, w1, v2, w2 = split_up(v, m, n)
	
	loss(outer(v1, w1) + outer(v2, w2), M3)
end

# ╔═╡ 7040dc72-f893-11ea-3d22-4fbd452faa41
ff2(v) = ff(v, m, n)

# ╔═╡ 1dbcf15a-f890-11ea-008c-8935edfbdb1c
ff(rand(total), m, n)

# ╔═╡ 00000000-0000-0000-0000-000000000001
PLUTO_PROJECT_TOML_CONTENTS = """
[deps]
ColorSchemes = "35d6a980-a343-548e-a6ea-1d62b119f2f4"
Colors = "5ae59095-9a9b-59fe-a467-6f913c188581"
ImageMagick = "6218d12a-5da1-5696-b52f-db25d2ecc6d1"
Images = "916415d5-f1e6-5110-898d-aaa5f9f070e0"
LaTeXStrings = "b964fa9f-0449-5b57-a5c2-d3ea65f4040f"
LinearAlgebra = "37e2e46d-f89d-539d-b4ee-838fcccc9c8e"
Plots = "91a5bcdd-55d7-5caf-9e0b-520d859cae80"
PlutoUI = "7f904dfe-b85e-4ff6-b463-dae2292396a8"
Statistics = "10745b16-79ce-11e8-11f9-7d13ad32a3b2"

[compat]
ColorSchemes = "~3.14.0"
Colors = "~0.12.8"
ImageMagick = "~1.2.1"
Images = "~0.24.1"
LaTeXStrings = "~1.2.1"
Plots = "~1.21.3"
PlutoUI = "~0.7.9"
"""

# ╔═╡ 00000000-0000-0000-0000-000000000002
PLUTO_MANIFEST_TOML_CONTENTS = """
# This file is machine-generated - editing it directly is not advised

[[AbstractFFTs]]
deps = ["LinearAlgebra"]
git-tree-sha1 = "485ee0867925449198280d4af84bdb46a2a404d0"
uuid = "621f4979-c628-5d54-868e-fcf4e3e8185c"
version = "1.0.1"

[[Adapt]]
deps = ["LinearAlgebra"]
git-tree-sha1 = "84918055d15b3114ede17ac6a7182f68870c16f7"
uuid = "79e6a3ab-5dfb-504d-930d-738a2a938a0e"
version = "3.3.1"

[[ArgTools]]
uuid = "0dad84c5-d112-42e6-8d28-ef12dabb789f"

[[ArrayInterface]]
deps = ["IfElse", "LinearAlgebra", "Requires", "SparseArrays", "Static"]
git-tree-sha1 = "d84c956c4c0548b4caf0e4e96cf5b6494b5b1529"
uuid = "4fba245c-0d91-5ea0-9b3e-6abc04ee57a9"
version = "3.1.32"

[[Artifacts]]
uuid = "56f22d72-fd6d-98f1-02f0-08ddc0907c33"

[[AxisAlgorithms]]
deps = ["LinearAlgebra", "Random", "SparseArrays", "WoodburyMatrices"]
git-tree-sha1 = "a4d07a1c313392a77042855df46c5f534076fab9"
uuid = "13072b0f-2c55-5437-9ae7-d433b7a33950"
version = "1.0.0"

[[AxisArrays]]
deps = ["Dates", "IntervalSets", "IterTools", "RangeArrays"]
git-tree-sha1 = "d127d5e4d86c7680b20c35d40b503c74b9a39b5e"
uuid = "39de3d68-74b9-583c-8d2d-e117c070f3a9"
version = "0.4.4"

[[Base64]]
uuid = "2a0f44e3-6c83-55bd-87e4-b1978d98bd5f"

[[Bzip2_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "19a35467a82e236ff51bc17a3a44b69ef35185a2"
uuid = "6e34b625-4abd-537c-b88f-471c36dfa7a0"
version = "1.0.8+0"

[[CEnum]]
git-tree-sha1 = "215a9aa4a1f23fbd05b92769fdd62559488d70e9"
uuid = "fa961155-64e5-5f13-b03f-caf6b980ea82"
version = "0.4.1"

[[Cairo_jll]]
deps = ["Artifacts", "Bzip2_jll", "Fontconfig_jll", "FreeType2_jll", "Glib_jll", "JLLWrappers", "LZO_jll", "Libdl", "Pixman_jll", "Pkg", "Xorg_libXext_jll", "Xorg_libXrender_jll", "Zlib_jll", "libpng_jll"]
git-tree-sha1 = "f2202b55d816427cd385a9a4f3ffb226bee80f99"
uuid = "83423d85-b0ee-5818-9007-b63ccbeb887a"
version = "1.16.1+0"

[[CatIndices]]
deps = ["CustomUnitRanges", "OffsetArrays"]
git-tree-sha1 = "a0f80a09780eed9b1d106a1bf62041c2efc995bc"
uuid = "aafaddc9-749c-510e-ac4f-586e18779b91"
version = "0.2.2"

[[ChainRulesCore]]
deps = ["Compat", "LinearAlgebra", "SparseArrays"]
git-tree-sha1 = "4ce9393e871aca86cc457d9f66976c3da6902ea7"
uuid = "d360d2e6-b24c-11e9-a2a3-2a2ae2dbcce4"
version = "1.4.0"

[[ColorSchemes]]
deps = ["ColorTypes", "Colors", "FixedPointNumbers", "Random"]
git-tree-sha1 = "9995eb3977fbf67b86d0a0a0508e83017ded03f2"
uuid = "35d6a980-a343-548e-a6ea-1d62b119f2f4"
version = "3.14.0"

[[ColorTypes]]
deps = ["FixedPointNumbers", "Random"]
git-tree-sha1 = "024fe24d83e4a5bf5fc80501a314ce0d1aa35597"
uuid = "3da002f7-5984-5a60-b8a6-cbb66c0b333f"
version = "0.11.0"

[[ColorVectorSpace]]
deps = ["ColorTypes", "FixedPointNumbers", "LinearAlgebra", "SpecialFunctions", "Statistics", "TensorCore"]
git-tree-sha1 = "a66a8e024807c4b3d186eb1cab2aff3505271f8e"
uuid = "c3611d14-8923-5661-9e6a-0046d554d3a4"
version = "0.9.6"

[[Colors]]
deps = ["ColorTypes", "FixedPointNumbers", "Reexport"]
git-tree-sha1 = "417b0ed7b8b838aa6ca0a87aadf1bb9eb111ce40"
uuid = "5ae59095-9a9b-59fe-a467-6f913c188581"
version = "0.12.8"

[[Compat]]
deps = ["Base64", "Dates", "DelimitedFiles", "Distributed", "InteractiveUtils", "LibGit2", "Libdl", "LinearAlgebra", "Markdown", "Mmap", "Pkg", "Printf", "REPL", "Random", "SHA", "Serialization", "SharedArrays", "Sockets", "SparseArrays", "Statistics", "Test", "UUIDs", "Unicode"]
git-tree-sha1 = "4866e381721b30fac8dda4c8cb1d9db45c8d2994"
uuid = "34da2185-b29b-5c13-b0c7-acf172513d20"
version = "3.37.0"

[[CompilerSupportLibraries_jll]]
deps = ["Artifacts", "Libdl"]
uuid = "e66e0078-7015-5450-92f7-15fbd957f2ae"

[[ComputationalResources]]
git-tree-sha1 = "52cb3ec90e8a8bea0e62e275ba577ad0f74821f7"
uuid = "ed09eef8-17a6-5b46-8889-db040fac31e3"
version = "0.3.2"

[[Contour]]
deps = ["StaticArrays"]
git-tree-sha1 = "9f02045d934dc030edad45944ea80dbd1f0ebea7"
uuid = "d38c429a-6771-53c6-b99e-75d170b6e991"
version = "0.5.7"

[[CoordinateTransformations]]
deps = ["LinearAlgebra", "StaticArrays"]
git-tree-sha1 = "6d1c23e740a586955645500bbec662476204a52c"
uuid = "150eb455-5306-5404-9cee-2592286d6298"
version = "0.6.1"

[[CustomUnitRanges]]
git-tree-sha1 = "1a3f97f907e6dd8983b744d2642651bb162a3f7a"
uuid = "dc8bdbbb-1ca9-579f-8c36-e416f6a65cce"
version = "1.0.2"

[[DataAPI]]
git-tree-sha1 = "bec2532f8adb82005476c141ec23e921fc20971b"
uuid = "9a962f9c-6df0-11e9-0e5d-c546b8b5ee8a"
version = "1.8.0"

[[DataStructures]]
deps = ["Compat", "InteractiveUtils", "OrderedCollections"]
git-tree-sha1 = "7d9d316f04214f7efdbb6398d545446e246eff02"
uuid = "864edb3b-99cc-5e75-8d2d-829cb0a9cfe8"
version = "0.18.10"

[[DataValueInterfaces]]
git-tree-sha1 = "bfc1187b79289637fa0ef6d4436ebdfe6905cbd6"
uuid = "e2d170a0-9d28-54be-80f0-106bbe20a464"
version = "1.0.0"

[[Dates]]
deps = ["Printf"]
uuid = "ade2ca70-3891-5945-98fb-dc099432e06a"

[[DelimitedFiles]]
deps = ["Mmap"]
uuid = "8bb1440f-4735-579b-a4ab-409b98df4dab"

[[Distances]]
deps = ["LinearAlgebra", "Statistics", "StatsAPI"]
git-tree-sha1 = "9f46deb4d4ee4494ffb5a40a27a2aced67bdd838"
uuid = "b4f34e82-e78d-54a5-968a-f98e89d6e8f7"
version = "0.10.4"

[[Distributed]]
deps = ["Random", "Serialization", "Sockets"]
uuid = "8ba89e20-285c-5b6f-9357-94700520ee1b"

[[DocStringExtensions]]
deps = ["LibGit2"]
git-tree-sha1 = "a32185f5428d3986f47c2ab78b1f216d5e6cc96f"
uuid = "ffbed154-4ef7-542d-bbb7-c09d3a79fcae"
version = "0.8.5"

[[Downloads]]
deps = ["ArgTools", "LibCURL", "NetworkOptions"]
uuid = "f43a241f-c20a-4ad4-852c-f6b1247861c6"

[[EarCut_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "3f3a2501fa7236e9b911e0f7a588c657e822bb6d"
uuid = "5ae413db-bbd1-5e63-b57d-d24a61df00f5"
version = "2.2.3+0"

[[EllipsisNotation]]
deps = ["ArrayInterface"]
git-tree-sha1 = "8041575f021cba5a099a456b4163c9a08b566a02"
uuid = "da5c29d0-fa7d-589e-88eb-ea29b0a81949"
version = "1.1.0"

[[Expat_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "b3bfd02e98aedfa5cf885665493c5598c350cd2f"
uuid = "2e619515-83b5-522b-bb60-26c02a35a201"
version = "2.2.10+0"

[[FFMPEG]]
deps = ["FFMPEG_jll"]
git-tree-sha1 = "b57e3acbe22f8484b4b5ff66a7499717fe1a9cc8"
uuid = "c87230d0-a227-11e9-1b43-d7ebe4e7570a"
version = "0.4.1"

[[FFMPEG_jll]]
deps = ["Artifacts", "Bzip2_jll", "FreeType2_jll", "FriBidi_jll", "JLLWrappers", "LAME_jll", "Libdl", "Ogg_jll", "OpenSSL_jll", "Opus_jll", "Pkg", "Zlib_jll", "libass_jll", "libfdk_aac_jll", "libvorbis_jll", "x264_jll", "x265_jll"]
git-tree-sha1 = "d8a578692e3077ac998b50c0217dfd67f21d1e5f"
uuid = "b22a6f82-2f65-5046-a5b2-351ab43fb4e5"
version = "4.4.0+0"

[[FFTViews]]
deps = ["CustomUnitRanges", "FFTW"]
git-tree-sha1 = "70a0cfd9b1c86b0209e38fbfe6d8231fd606eeaf"
uuid = "4f61f5a4-77b1-5117-aa51-3ab5ef4ef0cd"
version = "0.3.1"

[[FFTW]]
deps = ["AbstractFFTs", "FFTW_jll", "LinearAlgebra", "MKL_jll", "Preferences", "Reexport"]
git-tree-sha1 = "463cb335fa22c4ebacfd1faba5fde14edb80d96c"
uuid = "7a1cc6ca-52ef-59f5-83cd-3a7055c09341"
version = "1.4.5"

[[FFTW_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "3676abafff7e4ff07bbd2c42b3d8201f31653dcc"
uuid = "f5851436-0d7a-5f13-b9de-f02708fd171a"
version = "3.3.9+8"

[[FileIO]]
deps = ["Pkg", "Requires", "UUIDs"]
git-tree-sha1 = "3c041d2ac0a52a12a27af2782b34900d9c3ee68c"
uuid = "5789e2e9-d7fb-5bc7-8068-2c6fae9b9549"
version = "1.11.1"

[[FixedPointNumbers]]
deps = ["Statistics"]
git-tree-sha1 = "335bfdceacc84c5cdf16aadc768aa5ddfc5383cc"
uuid = "53c48c17-4a7d-5ca2-90c5-79b7896eea93"
version = "0.8.4"

[[Fontconfig_jll]]
deps = ["Artifacts", "Bzip2_jll", "Expat_jll", "FreeType2_jll", "JLLWrappers", "Libdl", "Libuuid_jll", "Pkg", "Zlib_jll"]
git-tree-sha1 = "21efd19106a55620a188615da6d3d06cd7f6ee03"
uuid = "a3f928ae-7b40-5064-980b-68af3947d34b"
version = "2.13.93+0"

[[Formatting]]
deps = ["Printf"]
git-tree-sha1 = "8339d61043228fdd3eb658d86c926cb282ae72a8"
uuid = "59287772-0a20-5a39-b81b-1366585eb4c0"
version = "0.4.2"

[[FreeType2_jll]]
deps = ["Artifacts", "Bzip2_jll", "JLLWrappers", "Libdl", "Pkg", "Zlib_jll"]
git-tree-sha1 = "87eb71354d8ec1a96d4a7636bd57a7347dde3ef9"
uuid = "d7e528f0-a631-5988-bf34-fe36492bcfd7"
version = "2.10.4+0"

[[FriBidi_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "aa31987c2ba8704e23c6c8ba8a4f769d5d7e4f91"
uuid = "559328eb-81f9-559d-9380-de523a88c83c"
version = "1.0.10+0"

[[GLFW_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Libglvnd_jll", "Pkg", "Xorg_libXcursor_jll", "Xorg_libXi_jll", "Xorg_libXinerama_jll", "Xorg_libXrandr_jll"]
git-tree-sha1 = "dba1e8614e98949abfa60480b13653813d8f0157"
uuid = "0656b61e-2033-5cc2-a64a-77c0f6c09b89"
version = "3.3.5+0"

[[GR]]
deps = ["Base64", "DelimitedFiles", "GR_jll", "HTTP", "JSON", "Libdl", "LinearAlgebra", "Pkg", "Printf", "Random", "Serialization", "Sockets", "Test", "UUIDs"]
git-tree-sha1 = "182da592436e287758ded5be6e32c406de3a2e47"
uuid = "28b8d3ca-fb5f-59d9-8090-bfdbd6d07a71"
version = "0.58.1"

[[GR_jll]]
deps = ["Artifacts", "Bzip2_jll", "Cairo_jll", "FFMPEG_jll", "Fontconfig_jll", "GLFW_jll", "JLLWrappers", "JpegTurbo_jll", "Libdl", "Libtiff_jll", "Pixman_jll", "Pkg", "Qt5Base_jll", "Zlib_jll", "libpng_jll"]
git-tree-sha1 = "ef49a187604f865f4708c90e3f431890724e9012"
uuid = "d2c73de3-f751-5644-a686-071e5b155ba9"
version = "0.59.0+0"

[[GeometryBasics]]
deps = ["EarCut_jll", "IterTools", "LinearAlgebra", "StaticArrays", "StructArrays", "Tables"]
git-tree-sha1 = "58bcdf5ebc057b085e58d95c138725628dd7453c"
uuid = "5c1252a2-5f33-56bf-86c9-59e7332b4326"
version = "0.4.1"

[[Gettext_jll]]
deps = ["Artifacts", "CompilerSupportLibraries_jll", "JLLWrappers", "Libdl", "Libiconv_jll", "Pkg", "XML2_jll"]
git-tree-sha1 = "9b02998aba7bf074d14de89f9d37ca24a1a0b046"
uuid = "78b55507-aeef-58d4-861c-77aaff3498b1"
version = "0.21.0+0"

[[Glib_jll]]
deps = ["Artifacts", "Gettext_jll", "JLLWrappers", "Libdl", "Libffi_jll", "Libiconv_jll", "Libmount_jll", "PCRE_jll", "Pkg", "Zlib_jll"]
git-tree-sha1 = "7bf67e9a481712b3dbe9cb3dac852dc4b1162e02"
uuid = "7746bdde-850d-59dc-9ae8-88ece973131d"
version = "2.68.3+0"

[[Graphics]]
deps = ["Colors", "LinearAlgebra", "NaNMath"]
git-tree-sha1 = "2c1cf4df419938ece72de17f368a021ee162762e"
uuid = "a2bd30eb-e257-5431-a919-1863eab51364"
version = "1.1.0"

[[Graphite2_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "344bf40dcab1073aca04aa0df4fb092f920e4011"
uuid = "3b182d85-2403-5c21-9c21-1e1f0cc25472"
version = "1.3.14+0"

[[Grisu]]
git-tree-sha1 = "53bb909d1151e57e2484c3d1b53e19552b887fb2"
uuid = "42e2da0e-8278-4e71-bc24-59509adca0fe"
version = "1.0.2"

[[HTTP]]
deps = ["Base64", "Dates", "IniFile", "Logging", "MbedTLS", "NetworkOptions", "Sockets", "URIs"]
git-tree-sha1 = "60ed5f1643927479f845b0135bb369b031b541fa"
uuid = "cd3eb016-35fb-5094-929b-558a96fad6f3"
version = "0.9.14"

[[HarfBuzz_jll]]
deps = ["Artifacts", "Cairo_jll", "Fontconfig_jll", "FreeType2_jll", "Glib_jll", "Graphite2_jll", "JLLWrappers", "Libdl", "Libffi_jll", "Pkg"]
git-tree-sha1 = "8a954fed8ac097d5be04921d595f741115c1b2ad"
uuid = "2e76f6c2-a576-52d4-95c1-20adfe4de566"
version = "2.8.1+0"

[[IdentityRanges]]
deps = ["OffsetArrays"]
git-tree-sha1 = "be8fcd695c4da16a1d6d0cd213cb88090a150e3b"
uuid = "bbac6d45-d8f3-5730-bfe4-7a449cd117ca"
version = "0.3.1"

[[IfElse]]
git-tree-sha1 = "28e837ff3e7a6c3cdb252ce49fb412c8eb3caeef"
uuid = "615f187c-cbe4-4ef1-ba3b-2fcf58d6d173"
version = "0.1.0"

[[ImageAxes]]
deps = ["AxisArrays", "ImageCore", "Reexport", "SimpleTraits"]
git-tree-sha1 = "794ad1d922c432082bc1aaa9fa8ffbd1fe74e621"
uuid = "2803e5a7-5153-5ecf-9a86-9b4c37f5f5ac"
version = "0.6.9"

[[ImageContrastAdjustment]]
deps = ["ColorVectorSpace", "ImageCore", "ImageTransformations", "Parameters"]
git-tree-sha1 = "2e6084db6cccab11fe0bc3e4130bd3d117092ed9"
uuid = "f332f351-ec65-5f6a-b3d1-319c6670881a"
version = "0.3.7"

[[ImageCore]]
deps = ["AbstractFFTs", "Colors", "FixedPointNumbers", "Graphics", "MappedArrays", "MosaicViews", "OffsetArrays", "PaddedViews", "Reexport"]
git-tree-sha1 = "db645f20b59f060d8cfae696bc9538d13fd86416"
uuid = "a09fc81d-aa75-5fe9-8630-4744c3626534"
version = "0.8.22"

[[ImageDistances]]
deps = ["ColorVectorSpace", "Distances", "ImageCore", "ImageMorphology", "LinearAlgebra", "Statistics"]
git-tree-sha1 = "6378c34a3c3a216235210d19b9f495ecfff2f85f"
uuid = "51556ac3-7006-55f5-8cb3-34580c88182d"
version = "0.2.13"

[[ImageFiltering]]
deps = ["CatIndices", "ColorVectorSpace", "ComputationalResources", "DataStructures", "FFTViews", "FFTW", "ImageCore", "LinearAlgebra", "OffsetArrays", "Requires", "SparseArrays", "StaticArrays", "Statistics", "TiledIteration"]
git-tree-sha1 = "bf96839133212d3eff4a1c3a80c57abc7cfbf0ce"
uuid = "6a3955dd-da59-5b1f-98d4-e7296123deb5"
version = "0.6.21"

[[ImageIO]]
deps = ["FileIO", "Netpbm", "OpenEXR", "PNGFiles", "TiffImages", "UUIDs"]
git-tree-sha1 = "13c826abd23931d909e4c5538643d9691f62a617"
uuid = "82e4d734-157c-48bb-816b-45c225c6df19"
version = "0.5.8"

[[ImageMagick]]
deps = ["FileIO", "ImageCore", "ImageMagick_jll", "InteractiveUtils", "Libdl", "Pkg", "Random"]
git-tree-sha1 = "5bc1cb62e0c5f1005868358db0692c994c3a13c6"
uuid = "6218d12a-5da1-5696-b52f-db25d2ecc6d1"
version = "1.2.1"

[[ImageMagick_jll]]
deps = ["JpegTurbo_jll", "Libdl", "Libtiff_jll", "Pkg", "Zlib_jll", "libpng_jll"]
git-tree-sha1 = "1c0a2295cca535fabaf2029062912591e9b61987"
uuid = "c73af94c-d91f-53ed-93a7-00f77d67a9d7"
version = "6.9.10-12+3"

[[ImageMetadata]]
deps = ["AxisArrays", "ColorVectorSpace", "ImageAxes", "ImageCore", "IndirectArrays"]
git-tree-sha1 = "ae76038347dc4edcdb06b541595268fca65b6a42"
uuid = "bc367c6b-8a6b-528e-b4bd-a4b897500b49"
version = "0.9.5"

[[ImageMorphology]]
deps = ["ColorVectorSpace", "ImageCore", "LinearAlgebra", "TiledIteration"]
git-tree-sha1 = "68e7cbcd7dfaa3c2f74b0a8ab3066f5de8f2b71d"
uuid = "787d08f9-d448-5407-9aad-5290dd7ab264"
version = "0.2.11"

[[ImageQualityIndexes]]
deps = ["ColorVectorSpace", "ImageCore", "ImageDistances", "ImageFiltering", "OffsetArrays", "Statistics"]
git-tree-sha1 = "1198f85fa2481a3bb94bf937495ba1916f12b533"
uuid = "2996bd0c-7a13-11e9-2da2-2f5ce47296a9"
version = "0.2.2"

[[ImageShow]]
deps = ["Base64", "FileIO", "ImageCore", "OffsetArrays", "Requires", "StackViews"]
git-tree-sha1 = "832abfd709fa436a562db47fd8e81377f72b01f9"
uuid = "4e3cecfd-b093-5904-9786-8bbb286a6a31"
version = "0.3.1"

[[ImageTransformations]]
deps = ["AxisAlgorithms", "ColorVectorSpace", "CoordinateTransformations", "IdentityRanges", "ImageCore", "Interpolations", "OffsetArrays", "Rotations", "StaticArrays"]
git-tree-sha1 = "e4cc551e4295a5c96545bb3083058c24b78d4cf0"
uuid = "02fcd773-0e25-5acc-982a-7f6622650795"
version = "0.8.13"

[[Images]]
deps = ["AxisArrays", "Base64", "ColorVectorSpace", "FileIO", "Graphics", "ImageAxes", "ImageContrastAdjustment", "ImageCore", "ImageDistances", "ImageFiltering", "ImageIO", "ImageMagick", "ImageMetadata", "ImageMorphology", "ImageQualityIndexes", "ImageShow", "ImageTransformations", "IndirectArrays", "OffsetArrays", "Random", "Reexport", "SparseArrays", "StaticArrays", "Statistics", "StatsBase", "TiledIteration"]
git-tree-sha1 = "8b714d5e11c91a0d945717430ec20f9251af4bd2"
uuid = "916415d5-f1e6-5110-898d-aaa5f9f070e0"
version = "0.24.1"

[[Imath_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "87f7662e03a649cffa2e05bf19c303e168732d3e"
uuid = "905a6f67-0a94-5f89-b386-d35d92009cd1"
version = "3.1.2+0"

[[IndirectArrays]]
git-tree-sha1 = "c2a145a145dc03a7620af1444e0264ef907bd44f"
uuid = "9b13fd28-a010-5f03-acff-a1bbcff69959"
version = "0.5.1"

[[Inflate]]
git-tree-sha1 = "f5fc07d4e706b84f72d54eedcc1c13d92fb0871c"
uuid = "d25df0c9-e2be-5dd7-82c8-3ad0b3e990b9"
version = "0.1.2"

[[IniFile]]
deps = ["Test"]
git-tree-sha1 = "098e4d2c533924c921f9f9847274f2ad89e018b8"
uuid = "83e8ac13-25f8-5344-8a64-a9f2b223428f"
version = "0.5.0"

[[IntelOpenMP_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "d979e54b71da82f3a65b62553da4fc3d18c9004c"
uuid = "1d5cc7b8-4909-519e-a0f8-d0f5ad9712d0"
version = "2018.0.3+2"

[[InteractiveUtils]]
deps = ["Markdown"]
uuid = "b77e0a4c-d291-57a0-90e8-8db25a27a240"

[[Interpolations]]
deps = ["AxisAlgorithms", "ChainRulesCore", "LinearAlgebra", "OffsetArrays", "Random", "Ratios", "Requires", "SharedArrays", "SparseArrays", "StaticArrays", "WoodburyMatrices"]
git-tree-sha1 = "61aa005707ea2cebf47c8d780da8dc9bc4e0c512"
uuid = "a98d9a8b-a2ab-59e6-89dd-64a1c18fca59"
version = "0.13.4"

[[IntervalSets]]
deps = ["Dates", "EllipsisNotation", "Statistics"]
git-tree-sha1 = "3cc368af3f110a767ac786560045dceddfc16758"
uuid = "8197267c-284f-5f27-9208-e0e47529a953"
version = "0.5.3"

[[IrrationalConstants]]
git-tree-sha1 = "f76424439413893a832026ca355fe273e93bce94"
uuid = "92d709cd-6900-40b7-9082-c6be49f344b6"
version = "0.1.0"

[[IterTools]]
git-tree-sha1 = "05110a2ab1fc5f932622ffea2a003221f4782c18"
uuid = "c8e1da08-722c-5040-9ed9-7db0dc04731e"
version = "1.3.0"

[[IteratorInterfaceExtensions]]
git-tree-sha1 = "a3f24677c21f5bbe9d2a714f95dcd58337fb2856"
uuid = "82899510-4779-5014-852e-03e436cf321d"
version = "1.0.0"

[[JLLWrappers]]
deps = ["Preferences"]
git-tree-sha1 = "642a199af8b68253517b80bd3bfd17eb4e84df6e"
uuid = "692b3bcd-3c85-4b1f-b108-f13ce0eb3210"
version = "1.3.0"

[[JSON]]
deps = ["Dates", "Mmap", "Parsers", "Unicode"]
git-tree-sha1 = "8076680b162ada2a031f707ac7b4953e30667a37"
uuid = "682c06a0-de6a-54ab-a142-c8b1cf79cde6"
version = "0.21.2"

[[JpegTurbo_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "d735490ac75c5cb9f1b00d8b5509c11984dc6943"
uuid = "aacddb02-875f-59d6-b918-886e6ef4fbf8"
version = "2.1.0+0"

[[LAME_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "f6250b16881adf048549549fba48b1161acdac8c"
uuid = "c1c5ebd0-6772-5130-a774-d5fcae4a789d"
version = "3.100.1+0"

[[LZO_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "e5b909bcf985c5e2605737d2ce278ed791b89be6"
uuid = "dd4b983a-f0e5-5f8d-a1b7-129d4a5fb1ac"
version = "2.10.1+0"

[[LaTeXStrings]]
git-tree-sha1 = "c7f1c695e06c01b95a67f0cd1d34994f3e7db104"
uuid = "b964fa9f-0449-5b57-a5c2-d3ea65f4040f"
version = "1.2.1"

[[Latexify]]
deps = ["Formatting", "InteractiveUtils", "LaTeXStrings", "MacroTools", "Markdown", "Printf", "Requires"]
git-tree-sha1 = "a4b12a1bd2ebade87891ab7e36fdbce582301a92"
uuid = "23fbe1c1-3f47-55db-b15f-69d7ec21a316"
version = "0.15.6"

[[LazyArtifacts]]
deps = ["Artifacts", "Pkg"]
uuid = "4af54fe1-eca0-43a8-85a7-787d91b784e3"

[[LibCURL]]
deps = ["LibCURL_jll", "MozillaCACerts_jll"]
uuid = "b27032c2-a3e7-50c8-80cd-2d36dbcbfd21"

[[LibCURL_jll]]
deps = ["Artifacts", "LibSSH2_jll", "Libdl", "MbedTLS_jll", "Zlib_jll", "nghttp2_jll"]
uuid = "deac9b47-8bc7-5906-a0fe-35ac56dc84c0"

[[LibGit2]]
deps = ["Base64", "NetworkOptions", "Printf", "SHA"]
uuid = "76f85450-5226-5b5a-8eaa-529ad045b433"

[[LibSSH2_jll]]
deps = ["Artifacts", "Libdl", "MbedTLS_jll"]
uuid = "29816b5a-b9ab-546f-933c-edad1886dfa8"

[[Libdl]]
uuid = "8f399da3-3557-5675-b5ff-fb832c97cbdb"

[[Libffi_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "761a393aeccd6aa92ec3515e428c26bf99575b3b"
uuid = "e9f186c6-92d2-5b65-8a66-fee21dc1b490"
version = "3.2.2+0"

[[Libgcrypt_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Libgpg_error_jll", "Pkg"]
git-tree-sha1 = "64613c82a59c120435c067c2b809fc61cf5166ae"
uuid = "d4300ac3-e22c-5743-9152-c294e39db1e4"
version = "1.8.7+0"

[[Libglvnd_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_libX11_jll", "Xorg_libXext_jll"]
git-tree-sha1 = "7739f837d6447403596a75d19ed01fd08d6f56bf"
uuid = "7e76a0d4-f3c7-5321-8279-8d96eeed0f29"
version = "1.3.0+3"

[[Libgpg_error_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "c333716e46366857753e273ce6a69ee0945a6db9"
uuid = "7add5ba3-2f88-524e-9cd5-f83b8a55f7b8"
version = "1.42.0+0"

[[Libiconv_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "42b62845d70a619f063a7da093d995ec8e15e778"
uuid = "94ce4f54-9a6c-5748-9c1c-f9c7231a4531"
version = "1.16.1+1"

[[Libmount_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "9c30530bf0effd46e15e0fdcf2b8636e78cbbd73"
uuid = "4b2f31a3-9ecc-558c-b454-b3730dcb73e9"
version = "2.35.0+0"

[[Libtiff_jll]]
deps = ["Artifacts", "JLLWrappers", "JpegTurbo_jll", "Libdl", "Pkg", "Zlib_jll", "Zstd_jll"]
git-tree-sha1 = "340e257aada13f95f98ee352d316c3bed37c8ab9"
uuid = "89763e89-9b03-5906-acba-b20f662cd828"
version = "4.3.0+0"

[[Libuuid_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "7f3efec06033682db852f8b3bc3c1d2b0a0ab066"
uuid = "38a345b3-de98-5d2b-a5d3-14cd9215e700"
version = "2.36.0+0"

[[LinearAlgebra]]
deps = ["Libdl"]
uuid = "37e2e46d-f89d-539d-b4ee-838fcccc9c8e"

[[LogExpFunctions]]
deps = ["ChainRulesCore", "DocStringExtensions", "IrrationalConstants", "LinearAlgebra"]
git-tree-sha1 = "34dc30f868e368f8a17b728a1238f3fcda43931a"
uuid = "2ab3a3ac-af41-5b50-aa03-7779005ae688"
version = "0.3.3"

[[Logging]]
uuid = "56ddb016-857b-54e1-b83d-db4d58db5568"

[[MKL_jll]]
deps = ["Artifacts", "IntelOpenMP_jll", "JLLWrappers", "LazyArtifacts", "Libdl", "Pkg"]
git-tree-sha1 = "c253236b0ed414624b083e6b72bfe891fbd2c7af"
uuid = "856f044c-d86e-5d09-b602-aeab76dc8ba7"
version = "2021.1.1+1"

[[MacroTools]]
deps = ["Markdown", "Random"]
git-tree-sha1 = "5a5bc6bf062f0f95e62d0fe0a2d99699fed82dd9"
uuid = "1914dd2f-81c6-5fcd-8719-6d5c9610ff09"
version = "0.5.8"

[[MappedArrays]]
git-tree-sha1 = "e8b359ef06ec72e8c030463fe02efe5527ee5142"
uuid = "dbb5928d-eab1-5f90-85c2-b9b0edb7c900"
version = "0.4.1"

[[Markdown]]
deps = ["Base64"]
uuid = "d6f4376e-aef5-505a-96c1-9c027394607a"

[[MbedTLS]]
deps = ["Dates", "MbedTLS_jll", "Random", "Sockets"]
git-tree-sha1 = "1c38e51c3d08ef2278062ebceade0e46cefc96fe"
uuid = "739be429-bea8-5141-9913-cc70e7f3736d"
version = "1.0.3"

[[MbedTLS_jll]]
deps = ["Artifacts", "Libdl"]
uuid = "c8ffd9c3-330d-5841-b78e-0817d7145fa1"

[[Measures]]
git-tree-sha1 = "e498ddeee6f9fdb4551ce855a46f54dbd900245f"
uuid = "442fdcdd-2543-5da2-b0f3-8c86c306513e"
version = "0.3.1"

[[Missings]]
deps = ["DataAPI"]
git-tree-sha1 = "bf210ce90b6c9eed32d25dbcae1ebc565df2687f"
uuid = "e1d29d7a-bbdc-5cf2-9ac0-f12de2c33e28"
version = "1.0.2"

[[Mmap]]
uuid = "a63ad114-7e13-5084-954f-fe012c677804"

[[MosaicViews]]
deps = ["MappedArrays", "OffsetArrays", "PaddedViews", "StackViews"]
git-tree-sha1 = "b34e3bc3ca7c94914418637cb10cc4d1d80d877d"
uuid = "e94cdb99-869f-56ef-bcf0-1ae2bcbe0389"
version = "0.3.3"

[[MozillaCACerts_jll]]
uuid = "14a3606d-f60d-562e-9121-12d972cd8159"

[[NaNMath]]
git-tree-sha1 = "bfe47e760d60b82b66b61d2d44128b62e3a369fb"
uuid = "77ba4419-2d1f-58cd-9bb1-8ffee604a2e3"
version = "0.3.5"

[[Netpbm]]
deps = ["ColorVectorSpace", "FileIO", "ImageCore"]
git-tree-sha1 = "09589171688f0039f13ebe0fdcc7288f50228b52"
uuid = "f09324ee-3d7c-5217-9330-fc30815ba969"
version = "1.0.1"

[[NetworkOptions]]
uuid = "ca575930-c2e3-43a9-ace4-1e988b2c1908"

[[OffsetArrays]]
deps = ["Adapt"]
git-tree-sha1 = "c870a0d713b51e4b49be6432eff0e26a4325afee"
uuid = "6fe1bfb0-de20-5000-8ca7-80f57d26f881"
version = "1.10.6"

[[Ogg_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "7937eda4681660b4d6aeeecc2f7e1c81c8ee4e2f"
uuid = "e7412a2a-1a6e-54c0-be00-318e2571c051"
version = "1.3.5+0"

[[OpenEXR]]
deps = ["Colors", "FileIO", "OpenEXR_jll"]
git-tree-sha1 = "327f53360fdb54df7ecd01e96ef1983536d1e633"
uuid = "52e1d378-f018-4a11-a4be-720524705ac7"
version = "0.3.2"

[[OpenEXR_jll]]
deps = ["Artifacts", "Imath_jll", "JLLWrappers", "Libdl", "Pkg", "Zlib_jll"]
git-tree-sha1 = "923319661e9a22712f24596ce81c54fc0366f304"
uuid = "18a262bb-aa17-5467-a713-aee519bc75cb"
version = "3.1.1+0"

[[OpenSSL_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "15003dcb7d8db3c6c857fda14891a539a8f2705a"
uuid = "458c3c95-2e84-50aa-8efc-19380b2a3a95"
version = "1.1.10+0"

[[OpenSpecFun_jll]]
deps = ["Artifacts", "CompilerSupportLibraries_jll", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "13652491f6856acfd2db29360e1bbcd4565d04f1"
uuid = "efe28fd5-8261-553b-a9e1-b2916fc3738e"
version = "0.5.5+0"

[[Opus_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "51a08fb14ec28da2ec7a927c4337e4332c2a4720"
uuid = "91d4177d-7536-5919-b921-800302f37372"
version = "1.3.2+0"

[[OrderedCollections]]
git-tree-sha1 = "85f8e6578bf1f9ee0d11e7bb1b1456435479d47c"
uuid = "bac558e1-5e72-5ebc-8fee-abe8a469f55d"
version = "1.4.1"

[[PCRE_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "b2a7af664e098055a7529ad1a900ded962bca488"
uuid = "2f80f16e-611a-54ab-bc61-aa92de5b98fc"
version = "8.44.0+0"

[[PNGFiles]]
deps = ["Base64", "CEnum", "ImageCore", "IndirectArrays", "OffsetArrays", "libpng_jll"]
git-tree-sha1 = "e14c485f6beee0c7a8dcf6128bf70b85f1fe201e"
uuid = "f57f5aa1-a3ce-4bc8-8ab9-96f992907883"
version = "0.3.9"

[[PaddedViews]]
deps = ["OffsetArrays"]
git-tree-sha1 = "646eed6f6a5d8df6708f15ea7e02a7a2c4fe4800"
uuid = "5432bcbf-9aad-5242-b902-cca2824c8663"
version = "0.5.10"

[[Parameters]]
deps = ["OrderedCollections", "UnPack"]
git-tree-sha1 = "2276ac65f1e236e0a6ea70baff3f62ad4c625345"
uuid = "d96e819e-fc66-5662-9728-84c9c7592b0a"
version = "0.12.2"

[[Parsers]]
deps = ["Dates"]
git-tree-sha1 = "438d35d2d95ae2c5e8780b330592b6de8494e779"
uuid = "69de0a69-1ddd-5017-9359-2bf0b02dc9f0"
version = "2.0.3"

[[Pixman_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "b4f5d02549a10e20780a24fce72bea96b6329e29"
uuid = "30392449-352a-5448-841d-b1acce4e97dc"
version = "0.40.1+0"

[[Pkg]]
deps = ["Artifacts", "Dates", "Downloads", "LibGit2", "Libdl", "Logging", "Markdown", "Printf", "REPL", "Random", "SHA", "Serialization", "TOML", "Tar", "UUIDs", "p7zip_jll"]
uuid = "44cfe95a-1eb2-52ea-b672-e2afdf69b78f"

[[PkgVersion]]
deps = ["Pkg"]
git-tree-sha1 = "a7a7e1a88853564e551e4eba8650f8c38df79b37"
uuid = "eebad327-c553-4316-9ea0-9fa01ccd7688"
version = "0.1.1"

[[PlotThemes]]
deps = ["PlotUtils", "Requires", "Statistics"]
git-tree-sha1 = "a3a964ce9dc7898193536002a6dd892b1b5a6f1d"
uuid = "ccf2f8ad-2431-5c83-bf29-c5338b663b6a"
version = "2.0.1"

[[PlotUtils]]
deps = ["ColorSchemes", "Colors", "Dates", "Printf", "Random", "Reexport", "Statistics"]
git-tree-sha1 = "2537ed3c0ed5e03896927187f5f2ee6a4ab342db"
uuid = "995b91a9-d308-5afd-9ec6-746e21dbc043"
version = "1.0.14"

[[Plots]]
deps = ["Base64", "Contour", "Dates", "Downloads", "FFMPEG", "FixedPointNumbers", "GR", "GeometryBasics", "JSON", "Latexify", "LinearAlgebra", "Measures", "NaNMath", "PlotThemes", "PlotUtils", "Printf", "REPL", "Random", "RecipesBase", "RecipesPipeline", "Reexport", "Requires", "Scratch", "Showoff", "SparseArrays", "Statistics", "StatsBase", "UUIDs"]
git-tree-sha1 = "2dbafeadadcf7dadff20cd60046bba416b4912be"
uuid = "91a5bcdd-55d7-5caf-9e0b-520d859cae80"
version = "1.21.3"

[[PlutoUI]]
deps = ["Base64", "Dates", "InteractiveUtils", "JSON", "Logging", "Markdown", "Random", "Reexport", "Suppressor"]
git-tree-sha1 = "44e225d5837e2a2345e69a1d1e01ac2443ff9fcb"
uuid = "7f904dfe-b85e-4ff6-b463-dae2292396a8"
version = "0.7.9"

[[Preferences]]
deps = ["TOML"]
git-tree-sha1 = "00cfd92944ca9c760982747e9a1d0d5d86ab1e5a"
uuid = "21216c6a-2e73-6563-6e65-726566657250"
version = "1.2.2"

[[Printf]]
deps = ["Unicode"]
uuid = "de0858da-6303-5e67-8744-51eddeeeb8d7"

[[ProgressMeter]]
deps = ["Distributed", "Printf"]
git-tree-sha1 = "afadeba63d90ff223a6a48d2009434ecee2ec9e8"
uuid = "92933f4c-e287-5a05-a399-4b506db050ca"
version = "1.7.1"

[[Qt5Base_jll]]
deps = ["Artifacts", "CompilerSupportLibraries_jll", "Fontconfig_jll", "Glib_jll", "JLLWrappers", "Libdl", "Libglvnd_jll", "OpenSSL_jll", "Pkg", "Xorg_libXext_jll", "Xorg_libxcb_jll", "Xorg_xcb_util_image_jll", "Xorg_xcb_util_keysyms_jll", "Xorg_xcb_util_renderutil_jll", "Xorg_xcb_util_wm_jll", "Zlib_jll", "xkbcommon_jll"]
git-tree-sha1 = "ad368663a5e20dbb8d6dc2fddeefe4dae0781ae8"
uuid = "ea2cea3b-5b76-57ae-a6ef-0a8af62496e1"
version = "5.15.3+0"

[[REPL]]
deps = ["InteractiveUtils", "Markdown", "Sockets", "Unicode"]
uuid = "3fa0cd96-eef1-5676-8a61-b3b8758bbffb"

[[Random]]
deps = ["Serialization"]
uuid = "9a3f8284-a2c9-5f02-9a11-845980a1fd5c"

[[RangeArrays]]
git-tree-sha1 = "b9039e93773ddcfc828f12aadf7115b4b4d225f5"
uuid = "b3c3ace0-ae52-54e7-9d0b-2c1406fd6b9d"
version = "0.3.2"

[[Ratios]]
deps = ["Requires"]
git-tree-sha1 = "7dff99fbc740e2f8228c6878e2aad6d7c2678098"
uuid = "c84ed2f1-dad5-54f0-aa8e-dbefe2724439"
version = "0.4.1"

[[RecipesBase]]
git-tree-sha1 = "44a75aa7a527910ee3d1751d1f0e4148698add9e"
uuid = "3cdcf5f2-1ef4-517c-9805-6587b60abb01"
version = "1.1.2"

[[RecipesPipeline]]
deps = ["Dates", "NaNMath", "PlotUtils", "RecipesBase"]
git-tree-sha1 = "d4491becdc53580c6dadb0f6249f90caae888554"
uuid = "01d81517-befc-4cb6-b9ec-a95719d0359c"
version = "0.4.0"

[[Reexport]]
git-tree-sha1 = "45e428421666073eab6f2da5c9d310d99bb12f9b"
uuid = "189a3867-3050-52da-a836-e630ba90ab69"
version = "1.2.2"

[[Requires]]
deps = ["UUIDs"]
git-tree-sha1 = "4036a3bd08ac7e968e27c203d45f5fff15020621"
uuid = "ae029012-a4dd-5104-9daa-d747884805df"
version = "1.1.3"

[[Rotations]]
deps = ["LinearAlgebra", "StaticArrays", "Statistics"]
git-tree-sha1 = "2ed8d8a16d703f900168822d83699b8c3c1a5cd8"
uuid = "6038ab10-8711-5258-84ad-4b1120ba62dc"
version = "1.0.2"

[[SHA]]
uuid = "ea8e919c-243c-51af-8825-aaa63cd721ce"

[[Scratch]]
deps = ["Dates"]
git-tree-sha1 = "0b4b7f1393cff97c33891da2a0bf69c6ed241fda"
uuid = "6c6a2e73-6563-6170-7368-637461726353"
version = "1.1.0"

[[Serialization]]
uuid = "9e88b42a-f829-5b0c-bbe9-9e923198166b"

[[SharedArrays]]
deps = ["Distributed", "Mmap", "Random", "Serialization"]
uuid = "1a1011a3-84de-559e-8e89-a11a2f7dc383"

[[Showoff]]
deps = ["Dates", "Grisu"]
git-tree-sha1 = "91eddf657aca81df9ae6ceb20b959ae5653ad1de"
uuid = "992d4aef-0814-514b-bc4d-f2e9a6c4116f"
version = "1.0.3"

[[SimpleTraits]]
deps = ["InteractiveUtils", "MacroTools"]
git-tree-sha1 = "5d7e3f4e11935503d3ecaf7186eac40602e7d231"
uuid = "699a6c99-e7fa-54fc-8d76-47d257e15c1d"
version = "0.9.4"

[[Sockets]]
uuid = "6462fe0b-24de-5631-8697-dd941f90decc"

[[SortingAlgorithms]]
deps = ["DataStructures"]
git-tree-sha1 = "b3363d7460f7d098ca0912c69b082f75625d7508"
uuid = "a2af1166-a08f-5f64-846c-94a0d3cef48c"
version = "1.0.1"

[[SparseArrays]]
deps = ["LinearAlgebra", "Random"]
uuid = "2f01184e-e22b-5df5-ae63-d93ebab69eaf"

[[SpecialFunctions]]
deps = ["ChainRulesCore", "LogExpFunctions", "OpenSpecFun_jll"]
git-tree-sha1 = "a322a9493e49c5f3a10b50df3aedaf1cdb3244b7"
uuid = "276daf66-3868-5448-9aa4-cd146d93841b"
version = "1.6.1"

[[StackViews]]
deps = ["OffsetArrays"]
git-tree-sha1 = "46e589465204cd0c08b4bd97385e4fa79a0c770c"
uuid = "cae243ae-269e-4f55-b966-ac2d0dc13c15"
version = "0.1.1"

[[Static]]
deps = ["IfElse"]
git-tree-sha1 = "a8f30abc7c64a39d389680b74e749cf33f872a70"
uuid = "aedffcd0-7271-4cad-89d0-dc628f76c6d3"
version = "0.3.3"

[[StaticArrays]]
deps = ["LinearAlgebra", "Random", "Statistics"]
git-tree-sha1 = "3240808c6d463ac46f1c1cd7638375cd22abbccb"
uuid = "90137ffa-7385-5640-81b9-e52037218182"
version = "1.2.12"

[[Statistics]]
deps = ["LinearAlgebra", "SparseArrays"]
uuid = "10745b16-79ce-11e8-11f9-7d13ad32a3b2"

[[StatsAPI]]
git-tree-sha1 = "1958272568dc176a1d881acb797beb909c785510"
uuid = "82ae8749-77ed-4fe6-ae5f-f523153014b0"
version = "1.0.0"

[[StatsBase]]
deps = ["DataAPI", "DataStructures", "LinearAlgebra", "Missings", "Printf", "Random", "SortingAlgorithms", "SparseArrays", "Statistics", "StatsAPI"]
git-tree-sha1 = "8cbbc098554648c84f79a463c9ff0fd277144b6c"
uuid = "2913bbd2-ae8a-5f71-8c99-4fb6c76f3a91"
version = "0.33.10"

[[StructArrays]]
deps = ["Adapt", "DataAPI", "StaticArrays", "Tables"]
git-tree-sha1 = "f41020e84127781af49fc12b7e92becd7f5dd0ba"
uuid = "09ab397b-f2b6-538f-b94a-2f83cf4a842a"
version = "0.6.2"

[[Suppressor]]
git-tree-sha1 = "a819d77f31f83e5792a76081eee1ea6342ab8787"
uuid = "fd094767-a336-5f1f-9728-57cf17d0bbfb"
version = "0.2.0"

[[TOML]]
deps = ["Dates"]
uuid = "fa267f1f-6049-4f14-aa54-33bafae1ed76"

[[TableTraits]]
deps = ["IteratorInterfaceExtensions"]
git-tree-sha1 = "c06b2f539df1c6efa794486abfb6ed2022561a39"
uuid = "3783bdb8-4a98-5b6b-af9a-565f29a5fe9c"
version = "1.0.1"

[[Tables]]
deps = ["DataAPI", "DataValueInterfaces", "IteratorInterfaceExtensions", "LinearAlgebra", "TableTraits", "Test"]
git-tree-sha1 = "1162ce4a6c4b7e31e0e6b14486a6986951c73be9"
uuid = "bd369af6-aec1-5ad0-b16a-f7cc5008161c"
version = "1.5.2"

[[Tar]]
deps = ["ArgTools", "SHA"]
uuid = "a4e569a6-e804-4fa4-b0f3-eef7a1d5b13e"

[[TensorCore]]
deps = ["LinearAlgebra"]
git-tree-sha1 = "1feb45f88d133a655e001435632f019a9a1bcdb6"
uuid = "62fd8b95-f654-4bbd-a8a5-9c27f68ccd50"
version = "0.1.1"

[[Test]]
deps = ["InteractiveUtils", "Logging", "Random", "Serialization"]
uuid = "8dfed614-e22c-5e08-85e1-65c5234f0b40"

[[TiffImages]]
deps = ["ColorTypes", "DocStringExtensions", "FileIO", "FixedPointNumbers", "IndirectArrays", "Inflate", "OffsetArrays", "OrderedCollections", "PkgVersion", "ProgressMeter"]
git-tree-sha1 = "632a8d4dbbad6627a4d2d21b1c6ebcaeebb1e1ed"
uuid = "731e570b-9d59-4bfa-96dc-6df516fadf69"
version = "0.4.2"

[[TiledIteration]]
deps = ["OffsetArrays"]
git-tree-sha1 = "52c5f816857bfb3291c7d25420b1f4aca0a74d18"
uuid = "06e1c1a7-607b-532d-9fad-de7d9aa2abac"
version = "0.3.0"

[[URIs]]
git-tree-sha1 = "97bbe755a53fe859669cd907f2d96aee8d2c1355"
uuid = "5c2747f8-b7ea-4ff2-ba2e-563bfd36b1d4"
version = "1.3.0"

[[UUIDs]]
deps = ["Random", "SHA"]
uuid = "cf7118a7-6976-5b1a-9a39-7adc72f591a4"

[[UnPack]]
git-tree-sha1 = "387c1f73762231e86e0c9c5443ce3b4a0a9a0c2b"
uuid = "3a884ed6-31ef-47d7-9d2a-63182c4928ed"
version = "1.0.2"

[[Unicode]]
uuid = "4ec0a83e-493e-50e2-b9ac-8f72acf5a8f5"

[[Wayland_jll]]
deps = ["Artifacts", "Expat_jll", "JLLWrappers", "Libdl", "Libffi_jll", "Pkg", "XML2_jll"]
git-tree-sha1 = "3e61f0b86f90dacb0bc0e73a0c5a83f6a8636e23"
uuid = "a2964d1f-97da-50d4-b82a-358c7fce9d89"
version = "1.19.0+0"

[[Wayland_protocols_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Wayland_jll"]
git-tree-sha1 = "2839f1c1296940218e35df0bbb220f2a79686670"
uuid = "2381bf8a-dfd0-557d-9999-79630e7b1b91"
version = "1.18.0+4"

[[WoodburyMatrices]]
deps = ["LinearAlgebra", "SparseArrays"]
git-tree-sha1 = "59e2ad8fd1591ea019a5259bd012d7aee15f995c"
uuid = "efce3f68-66dc-5838-9240-27a6d6f5f9b6"
version = "0.5.3"

[[XML2_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Libiconv_jll", "Pkg", "Zlib_jll"]
git-tree-sha1 = "1acf5bdf07aa0907e0a37d3718bb88d4b687b74a"
uuid = "02c8fc9c-b97f-50b9-bbe4-9be30ff0a78a"
version = "2.9.12+0"

[[XSLT_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Libgcrypt_jll", "Libgpg_error_jll", "Libiconv_jll", "Pkg", "XML2_jll", "Zlib_jll"]
git-tree-sha1 = "91844873c4085240b95e795f692c4cec4d805f8a"
uuid = "aed1982a-8fda-507f-9586-7b0439959a61"
version = "1.1.34+0"

[[Xorg_libX11_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_libxcb_jll", "Xorg_xtrans_jll"]
git-tree-sha1 = "5be649d550f3f4b95308bf0183b82e2582876527"
uuid = "4f6342f7-b3d2-589e-9d20-edeb45f2b2bc"
version = "1.6.9+4"

[[Xorg_libXau_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "4e490d5c960c314f33885790ed410ff3a94ce67e"
uuid = "0c0b7dd1-d40b-584c-a123-a41640f87eec"
version = "1.0.9+4"

[[Xorg_libXcursor_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_libXfixes_jll", "Xorg_libXrender_jll"]
git-tree-sha1 = "12e0eb3bc634fa2080c1c37fccf56f7c22989afd"
uuid = "935fb764-8cf2-53bf-bb30-45bb1f8bf724"
version = "1.2.0+4"

[[Xorg_libXdmcp_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "4fe47bd2247248125c428978740e18a681372dd4"
uuid = "a3789734-cfe1-5b06-b2d0-1dd0d9d62d05"
version = "1.1.3+4"

[[Xorg_libXext_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_libX11_jll"]
git-tree-sha1 = "b7c0aa8c376b31e4852b360222848637f481f8c3"
uuid = "1082639a-0dae-5f34-9b06-72781eeb8cb3"
version = "1.3.4+4"

[[Xorg_libXfixes_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_libX11_jll"]
git-tree-sha1 = "0e0dc7431e7a0587559f9294aeec269471c991a4"
uuid = "d091e8ba-531a-589c-9de9-94069b037ed8"
version = "5.0.3+4"

[[Xorg_libXi_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_libXext_jll", "Xorg_libXfixes_jll"]
git-tree-sha1 = "89b52bc2160aadc84d707093930ef0bffa641246"
uuid = "a51aa0fd-4e3c-5386-b890-e753decda492"
version = "1.7.10+4"

[[Xorg_libXinerama_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_libXext_jll"]
git-tree-sha1 = "26be8b1c342929259317d8b9f7b53bf2bb73b123"
uuid = "d1454406-59df-5ea1-beac-c340f2130bc3"
version = "1.1.4+4"

[[Xorg_libXrandr_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_libXext_jll", "Xorg_libXrender_jll"]
git-tree-sha1 = "34cea83cb726fb58f325887bf0612c6b3fb17631"
uuid = "ec84b674-ba8e-5d96-8ba1-2a689ba10484"
version = "1.5.2+4"

[[Xorg_libXrender_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_libX11_jll"]
git-tree-sha1 = "19560f30fd49f4d4efbe7002a1037f8c43d43b96"
uuid = "ea2f1a96-1ddc-540d-b46f-429655e07cfa"
version = "0.9.10+4"

[[Xorg_libpthread_stubs_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "6783737e45d3c59a4a4c4091f5f88cdcf0908cbb"
uuid = "14d82f49-176c-5ed1-bb49-ad3f5cbd8c74"
version = "0.1.0+3"

[[Xorg_libxcb_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "XSLT_jll", "Xorg_libXau_jll", "Xorg_libXdmcp_jll", "Xorg_libpthread_stubs_jll"]
git-tree-sha1 = "daf17f441228e7a3833846cd048892861cff16d6"
uuid = "c7cfdc94-dc32-55de-ac96-5a1b8d977c5b"
version = "1.13.0+3"

[[Xorg_libxkbfile_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_libX11_jll"]
git-tree-sha1 = "926af861744212db0eb001d9e40b5d16292080b2"
uuid = "cc61e674-0454-545c-8b26-ed2c68acab7a"
version = "1.1.0+4"

[[Xorg_xcb_util_image_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_xcb_util_jll"]
git-tree-sha1 = "0fab0a40349ba1cba2c1da699243396ff8e94b97"
uuid = "12413925-8142-5f55-bb0e-6d7ca50bb09b"
version = "0.4.0+1"

[[Xorg_xcb_util_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_libxcb_jll"]
git-tree-sha1 = "e7fd7b2881fa2eaa72717420894d3938177862d1"
uuid = "2def613f-5ad1-5310-b15b-b15d46f528f5"
version = "0.4.0+1"

[[Xorg_xcb_util_keysyms_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_xcb_util_jll"]
git-tree-sha1 = "d1151e2c45a544f32441a567d1690e701ec89b00"
uuid = "975044d2-76e6-5fbe-bf08-97ce7c6574c7"
version = "0.4.0+1"

[[Xorg_xcb_util_renderutil_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_xcb_util_jll"]
git-tree-sha1 = "dfd7a8f38d4613b6a575253b3174dd991ca6183e"
uuid = "0d47668e-0667-5a69-a72c-f761630bfb7e"
version = "0.3.9+1"

[[Xorg_xcb_util_wm_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_xcb_util_jll"]
git-tree-sha1 = "e78d10aab01a4a154142c5006ed44fd9e8e31b67"
uuid = "c22f9ab0-d5fe-5066-847c-f4bb1cd4e361"
version = "0.4.1+1"

[[Xorg_xkbcomp_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_libxkbfile_jll"]
git-tree-sha1 = "4bcbf660f6c2e714f87e960a171b119d06ee163b"
uuid = "35661453-b289-5fab-8a00-3d9160c6a3a4"
version = "1.4.2+4"

[[Xorg_xkeyboard_config_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Xorg_xkbcomp_jll"]
git-tree-sha1 = "5c8424f8a67c3f2209646d4425f3d415fee5931d"
uuid = "33bec58e-1273-512f-9401-5d533626f822"
version = "2.27.0+4"

[[Xorg_xtrans_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "79c31e7844f6ecf779705fbc12146eb190b7d845"
uuid = "c5fb5394-a638-5e4d-96e5-b29de1b5cf10"
version = "1.4.0+3"

[[Zlib_jll]]
deps = ["Libdl"]
uuid = "83775a58-1f1d-513f-b197-d71354ab007a"

[[Zstd_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "cc4bf3fdde8b7e3e9fa0351bdeedba1cf3b7f6e6"
uuid = "3161d3a3-bdf6-5164-811a-617609db77b4"
version = "1.5.0+0"

[[libass_jll]]
deps = ["Artifacts", "Bzip2_jll", "FreeType2_jll", "FriBidi_jll", "HarfBuzz_jll", "JLLWrappers", "Libdl", "Pkg", "Zlib_jll"]
git-tree-sha1 = "5982a94fcba20f02f42ace44b9894ee2b140fe47"
uuid = "0ac62f75-1d6f-5e53-bd7c-93b484bb37c0"
version = "0.15.1+0"

[[libfdk_aac_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "daacc84a041563f965be61859a36e17c4e4fcd55"
uuid = "f638f0a6-7fb0-5443-88ba-1cc74229b280"
version = "2.0.2+0"

[[libpng_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Zlib_jll"]
git-tree-sha1 = "94d180a6d2b5e55e447e2d27a29ed04fe79eb30c"
uuid = "b53b4c65-9356-5827-b1ea-8c7a1a84506f"
version = "1.6.38+0"

[[libvorbis_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Ogg_jll", "Pkg"]
git-tree-sha1 = "c45f4e40e7aafe9d086379e5578947ec8b95a8fb"
uuid = "f27f6e37-5d2b-51aa-960f-b287f2bc3b7a"
version = "1.3.7+0"

[[nghttp2_jll]]
deps = ["Artifacts", "Libdl"]
uuid = "8e850ede-7688-5339-a07c-302acd2aaf8d"

[[p7zip_jll]]
deps = ["Artifacts", "Libdl"]
uuid = "3f19e933-33d8-53b3-aaab-bd5110c3b7a0"

[[x264_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "4fea590b89e6ec504593146bf8b988b2c00922b2"
uuid = "1270edf5-f2f9-52d2-97e9-ab00b5d0237a"
version = "2021.5.5+0"

[[x265_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg"]
git-tree-sha1 = "ee567a171cce03570d77ad3a43e90218e38937a9"
uuid = "dfaa095f-4041-5dcd-9319-2fabd8486b76"
version = "3.5.0+0"

[[xkbcommon_jll]]
deps = ["Artifacts", "JLLWrappers", "Libdl", "Pkg", "Wayland_jll", "Wayland_protocols_jll", "Xorg_libxcb_jll", "Xorg_xkeyboard_config_jll"]
git-tree-sha1 = "ece2350174195bb31de1a63bea3a41ae1aa593b6"
uuid = "d8fb68d0-12a3-5cfd-a85a-d49703b185fd"
version = "0.9.1+5"
"""

# ╔═╡ Cell order:
# ╟─4c1ebac8-81b7-11eb-19fa-f704b4d84a21
# ╠═cf82077a-81c2-11eb-1de2-09ed6c35d810
# ╠═c593a748-81b6-11eb-295a-a9800f9dec6d
# ╟─deb2af50-8524-11eb-0dd4-9d799ff6d3e2
# ╟─2e50a070-853f-11eb-2045-b1cc43c29768
# ╟─ed7ff6b2-f863-11ea-1a59-eb242a8674e3
# ╟─fed5845e-f863-11ea-2f95-c331d3c62647
# ╠═0e1a6d80-f864-11ea-074a-5f7890180114
# ╠═2e497e30-f895-11ea-09f1-d7f2c1f61193
# ╟─cfdd04f8-815a-11eb-0409-79a2599c29ab
# ╟─ab3d55cc-f905-11ea-2f22-5398f3aca803
# ╠═13b6c108-f864-11ea-2447-2b0741f15c7b
# ╠═e66b30a6-f914-11ea-2c0f-35282d45a30a
# ╠═43bff19e-f864-11ea-2315-0f85b532a325
# ╠═71d1b12e-f895-11ea-39df-f5c18a7766c3
# ╠═79d2c6f4-f895-11ea-30c4-9d1102c99482
# ╟─356267fa-815b-11eb-1c57-ad14fd6e91a7
# ╟─cdbe1d8e-f905-11ea-3884-efeeef386dda
# ╟─d9aa9af0-f865-11ea-379e-f16b452bd94c
# ╟─2e8ae92a-f867-11ea-0219-1bdd9627c1ea
# ╠═9ad13804-815c-11eb-0253-8f8baf15eee3
# ╠═38adc490-f867-11ea-1de5-3b633aff7c97
# ╠═b183b6ca-f864-11ea-0b34-4dd3f4f5e69d
# ╟─946fde3c-815b-11eb-3039-db4105bc43ab
# ╟─ab924210-815b-11eb-07fe-411db58fbc3a
# ╠═74c04322-815b-11eb-2308-7b3d571cf613
# ╟─b5094384-815b-11eb-06fd-1f40134c6fd8
# ╟─cc4f3fee-815b-11eb-2982-9b797b806b45
# ╟─dc55775a-815b-11eb-15b7-7993190bffab
# ╟─9cf23f9a-f864-11ea-3a08-af448aceefd8
# ╠═a5b62530-f864-11ea-21e8-71ccfed487f8
# ╠═f6713bec-815b-11eb-2fc4-6b0326a64b16
# ╠═5471ddce-f867-11ea-2519-21981f5ea68b
# ╟─c41df86c-f865-11ea-1253-4942bbdbe9d2
# ╟─7fca33ac-f864-11ea-2a8b-933eb382c172
# ╟─283f5da4-f866-11ea-27d4-957ca2551b92
# ╠═1957f71c-f8eb-11ea-0dcf-339bfa7f96fc
# ╠═54977286-f908-11ea-166d-d1df33f38454
# ╠═7b4e90b4-f866-11ea-26b3-95efde6c650b
# ╟─f574ad7c-f866-11ea-0efa-d9d0602aa63b
# ╟─8775b3fe-f866-11ea-3e6f-9732e39a3525
# ╠═cb7e85e4-815c-11eb-3923-7b9537801bae
# ╠═1147cbda-f867-11ea-08fa-ef6ed2ae1e93
# ╟─8a611e36-f867-11ea-121f-317b7c145fe3
# ╟─f7371934-f867-11ea-3b53-d1566684585c
# ╟─987c1f2e-f868-11ea-1125-0d8c02843ae4
# ╟─9e78b048-f868-11ea-192e-d903265d1eb5
# ╟─24df1f32-ec90-11ea-1f6d-03c1bfa5df8e
# ╟─b264f724-81bf-11eb-1052-295b81cde5fb
# ╟─09991230-8526-11eb-04aa-fb904bd2036c
# ╠═aec46a9b-f743-4cbd-97a7-3ef3cac78b12
# ╠═1b8c743e-ec90-11ea-10aa-e3b94f768f82
# ╟─eb867f18-852e-11eb-005f-e15b6d0d0d95
# ╟─f7079016-852e-11eb-3bc9-53fa0846276f
# ╟─870d3efa-f8fc-11ea-1593-1552511dcf86
# ╠═4fb82f18-852f-11eb-278d-cf93571f4adc
# ╟─5fcf832c-852f-11eb-1354-792933a891a5
# ╟─ef4a2a54-81bf-11eb-358b-0da2072f20c8
# ╟─f5358ce4-f86a-11ea-2989-b1f37be89183
# ╠═2c3721da-f86b-11ea-36cf-3fe4c6622dc6
# ╟─03ab44c0-f8fd-11ea-2243-1f3580f98a65
# ╟─6dec0db8-ec93-11ea-24ad-e17870ee64c2
# ╟─5fab2c32-f86b-11ea-2f27-ed5feaac1fa5
# ╟─ae9a2900-ec93-11ea-1ae5-0748221328fc
# ╟─b81c9db2-ec93-11ea-0dbd-4bd0951cb2cc
# ╟─80722856-f86d-11ea-363d-53fc5f6b8152
# ╟─b8fa6a1c-f86d-11ea-3d6b-2959d737254b
# ╟─3547f296-f86f-11ea-1698-53d3c1a0bc30
# ╟─7a83101e-f871-11ea-1d87-4946162777b5
# ╠═e8276b4e-f86f-11ea-38be-218a72452b10
# ╠═1f373bd0-853f-11eb-0f8e-19cb7f376182
# ╠═31e4b138-84e8-11eb-36a8-8b90746fbb0f
# ╟─d71fdaea-f86f-11ea-1a1f-45e4d50926d3
# ╠═757c6808-f8fe-11ea-39bb-47e4da65113a
# ╠═1232e848-8540-11eb-089b-2185cc06f23a
# ╠═3b71142c-f86f-11ea-0d43-47011d00786c
# ╠═88bbe1bc-f86f-11ea-3b6b-29175ddbea04
# ╠═7cb04c9a-8358-11eb-1255-8d8c90916c37
# ╟─cd9e05ee-f86f-11ea-0422-25f8329c7ef2
# ╟─7eb51908-f906-11ea-19d2-e947d81cb743
# ╠═8b8e6b2e-8531-11eb-1ea6-637db25b28d5
# ╟─4f1980ea-f86f-11ea-3df2-35cca6c961f3
# ╟─c9da6e64-8540-11eb-3984-47fdf8be0dac
# ╠═f70065aa-835a-11eb-00cb-ffa27bcb486e
# ╠═2ffe7ed0-f870-11ea-06aa-390581500ca1
# ╟─a5cdad52-f906-11ea-0486-755a6403a367
# ╟─0115c974-f871-11ea-1204-054510848849
# ╠═0935c870-f871-11ea-2a0b-b1b824379350
# ╠═6646abe0-835b-11eb-328a-55ca22f89c7d
# ╠═ef850e8e-84e7-11eb-1cb0-870c3000841d
# ╠═031a2894-84e8-11eb-1381-9b9e86f2fa0a
# ╟─e4af4d26-f877-11ea-1de3-a9f8d389138e
# ╟─bf57f674-f906-11ea-08eb-9b50818a025b
# ╠═17e015fe-f8ff-11ea-17b4-a3aa072cd7b3
# ╟─045b9b98-f8ff-11ea-0d49-5b209319e951
# ╟─cfec1ec4-f8ff-11ea-265d-ab4844f0f739
# ╠═e6e900b8-f904-11ea-2a0d-953b99785553
# ╟─301f4d06-8162-11eb-1cd6-31dd8da164b6
# ╟─aaff88e8-f877-11ea-1527-ff4d3db663db
# ╟─aefa84de-f877-11ea-3e26-678008e9739e
# ╟─0bd9358e-f879-11ea-2c83-ed4e7bf9d903
# ╟─9960e1c2-85a0-11eb-3d68-cd3a07a9c0b5
# ╟─dc4cca88-85a0-11eb-2791-d7610a610e36
# ╠═453689c2-85a2-11eb-2cbc-7d6476b42f2f
# ╟─91b77acc-85a2-11eb-19bb-bd2bdd0c1a68
# ╠═f92c75f6-85a3-11eb-1689-23aeaa3daeb7
# ╠═03069da6-85a4-11eb-2ac5-87b767846550
# ╠═40b87cbe-85a4-11eb-30f8-cf7b5e79c19a
# ╠═28a7d6dc-85a5-11eb-0e4b-b7e9b4c592ed
# ╟─1647a126-85a4-11eb-3923-5f5a6f703403
# ╠═6ec7f980-85a5-11eb-12fc-cb132db28d83
# ╟─92a2827e-84e9-11eb-1e85-6f49b1da7277
# ╟─e84adec2-84e8-11eb-2157-dd491588ccf0
# ╟─571b88ac-85a6-11eb-2887-a368a19bce4d
# ╠═12010a58-84eb-11eb-106f-cb4e3e0c879b
# ╠═696d2768-84eb-11eb-39e0-612e074a2c27
# ╠═7b7b5128-84eb-11eb-3974-9b4c08fab8bb
# ╟─805b9616-85a7-11eb-22e8-db8ee67071ae
# ╠═90656ce6-84fb-11eb-1aac-4bd7747613db
# ╠═aec542a2-84fb-11eb-322c-27fc2c45f6ef
# ╠═b55dcfd2-84fb-11eb-1766-17dc8b7a17d0
# ╟─1cf3e098-f864-11ea-3f3a-c53017b73490
# ╠═2917943c-f864-11ea-3ee6-db952ca7cd67
# ╠═72bb11b0-f88f-11ea-0e55-b1108300f854
# ╠═feeeb24a-f88f-11ea-287f-219e53615f32
# ╠═0bcc8852-f890-11ea-3715-11cbead7f636
# ╠═7040dc72-f893-11ea-3d22-4fbd452faa41
# ╠═1dbcf15a-f890-11ea-008c-8935edfbdb1c
# ╟─00000000-0000-0000-0000-000000000001
# ╟─00000000-0000-0000-0000-000000000002
