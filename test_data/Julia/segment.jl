# Geometry for vectors/arrows/segments


struct SegmentGeometry <: Gadfly.GeometryElement
    default_statistic::Gadfly.StatisticElement
    arrow::Bool
    filled::Bool
    tag::Symbol 
end 
SegmentGeometry(default_statistic=Gadfly.Stat.identity(); arrow=false, filled=false, tag=empty_tag) = 
    SegmentGeometry(default_statistic, arrow, filled, tag) 

"""
    Geom.segment[(; arrow=false, filled=false)]

Draw line segments from `x`, `y` to `xend`, `yend`.  Optionally specify their
`color`.  If `arrow` is `true` a `Scale` object for both axes must be
provided.  If `filled` is `true` the arrows are drawn with a filled polygon,
otherwise with a stroked line.
"""
const segment = SegmentGeometry

# Leave this as a function, pending extra arguments e.g. arrow attributes
"""
    Geom.vector[(; filled=false)]

This geometry is equivalent to [`Geom.segment(arrow=true)`](@ref).
"""
vector(; filled::Bool=false) = SegmentGeometry(arrow=true, filled=filled)

"""
    Geom.hair[(; intercept=0.0, orientation=:vertical)]

Draw lines from `x`, `y` to y=`intercept` if `orientation` is `:vertical` or
x=`intercept` if `:horizontal`.  Optionally specify their `color`.  This geometry
is equivalent to [`Geom.segment`](@ref) with [`Stat.hair`](@ref).
"""
hair(; intercept=0.0, orientation=:vertical) =
    SegmentGeometry(Gadfly.Stat.hair(intercept, orientation))

"""
    Geom.vectorfield[(; smoothness=1.0, scale=1.0, samples=20, filled=false)]

Draw a gradient vector field of the 2D function or a matrix in the `z`
aesthetic.  This geometry is equivalent to [`Geom.segment`](@ref) with
[`Stat.vectorfield`](@ref); see the latter for more information.
"""
function vectorfield(;smoothness=1.0, scale=1.0, samples=20, filled::Bool=false)
    return SegmentGeometry(
        Gadfly.Stat.vectorfield(smoothness, scale, samples), 
        arrow=true, filled=filled )
end

default_statistic(geom::SegmentGeometry) = geom.default_statistic
element_aesthetics(::SegmentGeometry) = [:x, :y, :xend, :yend, :color, :linestyle]


function render(geom::SegmentGeometry, theme::Gadfly.Theme, aes::Gadfly.Aesthetics)
    
    Gadfly.assert_aesthetics_defined("Geom.segment", aes, :x, :y, :xend, :yend)

    function arrow(x::Real, y::Real, xmax::Real, ymax::Real, xyrange::Vector{<:Real})
        dx = xmax-x
        dy = ymax-y
        vl = 0.225*hypot(dy/xyrange[2], dx/xyrange[1])
        ?? =  atan(dy/xyrange[2], dx/xyrange[1])
        ?? = pi/15
        xr = -vl*xyrange[1]*[cos(??+??), cos(??-??)]
        yr = -vl*xyrange[2]*[sin(??+??), sin(??-??)]
        [ (xmax+xr[1],ymax+yr[1]), (xmax,ymax), (xmax+xr[2],ymax+yr[2]) ]
    end


    default_aes = Gadfly.Aesthetics()
    default_aes.color = [theme.default_color]
    default_aes.linestyle = theme.line_style[1:1]
    aes = inherit(aes, default_aes)

    # Render lines, using multivariate groupings:
    XT, YT = eltype(aes.x), eltype(aes.y)
    CT, LST = eltype(aes.color), eltype(aes.linestyle)
    groups = collect(Tuple{CT, LST}, Compose.cyclezip(aes.color, aes.linestyle))
    ugroups = unique(groups)
    ulength1 = length(ugroups)==1
    

    # Geom.vector requires information about scales
    if geom.arrow
        check = [aes.xviewmin, aes.xviewmax, aes.yviewmin, aes.yviewmax ]
        if any( map(x -> x === nothing, check) )
            error("For Geom.vector, Scale minvalue and maxvalue must be manually provided for both axes")
        end
         xyrange = [aes.xviewmax-aes.xviewmin, aes.yviewmax-aes.yviewmin]

         arrows = [ arrow(x, y, xend, yend, xyrange)
                for (x, y, xend, yend) in Compose.cyclezip(aes.x, aes.y, aes.xend, aes.yend) ]
    end
    
    segments = [[(x,y), (xend,yend)]
        for (x, y, xend, yend) in Compose.cyclezip(aes.x, aes.y, aes.xend, aes.yend)]
       
    nsegs = length(segments)
    cs = Vector{CT}(undef, ulength1 ? 1 : nsegs)
    lss = Vector{LST}(undef, ulength1 ? 1 : nsegs)
    linestyle_palette_length = length(theme.line_style)
    if ulength1
        cs[1], lss[1] = groups[1]
    else
        for (k,(s,g)) in enumerate(zip(segments, cycle(groups)))
            cs[k], lss[k] = g
        end
    end

    linestyles = Gadfly.get_stroke_vector.(LST<:Int ?
        theme.line_style[mod1.(lss, linestyle_palette_length)] : lss)
    
    classes = svg_color_class_from_label.(aes.color_label(cs))

    ctx = context()
    compose!(ctx, (context(), Compose.line(segments, geom.tag),
                stroke(cs), linewidth(theme.line_width), 
                strokedash(linestyles), svgclass(classes)),
              svgclass("geometry"))
    if geom.arrow
        if geom.filled
            compose!(ctx, (context(), Compose.polygon(arrows), fill(cs), strokedash([])))
        else
            compose!(ctx, (context(), Compose.line(arrows), stroke(cs), linewidth(theme.line_width),
            strokedash([])))
        end
    end


    return ctx
end
