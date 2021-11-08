FROM ubuntu

ARG DEBIAN_FRONTEND=noninteractive
RUN apt update

RUN apt-get install -y cmake pkg-config libicu-dev zlib1g-dev libcurl4-openssl-dev libssl-dev ruby-dev
RUN apt -y install git
RUN apt install -y golang-go

RUN apt install -y python3-pip

COPY . /jbt/
WORKDIR /jbt/

RUN pip install -r requirements.txt

RUN go get github.com/go-enry/go-enry/v2; exit 0

RUN mv /root/go/src/github.com/go-enry/go-enry /root/go/src/github.com/go-enry/v2
RUN mkdir /root/go/src/github.com/go-enry/go-enry
RUN mv /root/go/src/github.com/go-enry/v2 /root/go/src/github.com/go-enry/go-enry/v2