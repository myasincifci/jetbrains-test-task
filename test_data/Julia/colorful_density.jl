using Gadfly, RDatasets

set_default_plot_size(6inch, 3inch)

plot(dataset("ggplot2", "diamonds"), x=:Price, color=:Cut, Geom.density)
