FROM ubuntu

ARG DEBIAN_FRONTEND=noninteractive
RUN apt update

RUN apt-get install -y cmake pkg-config libicu-dev zlib1g-dev libcurl4-openssl-dev libssl-dev ruby-dev
RUN apt install -y golang-go

RUN gem install github-linguist
RUN go get github.com/go-enry/enry
