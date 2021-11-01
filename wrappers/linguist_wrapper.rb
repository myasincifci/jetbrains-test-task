require 'linguist' 

ARGV.each {
    |x| puts Linguist::FileBlob.new(x).language
}

# puts ARGV[0]

# blob = Linguist::Blob.new("", ARGV.join(" "))
# blob = Linguist::FileBlob.new(Dir.getwd + "/example.c")

# language = blob.language

# puts language