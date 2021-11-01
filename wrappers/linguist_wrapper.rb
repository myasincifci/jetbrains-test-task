require 'linguist'

language = Linguist::Blob.new("", ARGV.join(" ")).language
puts language.name