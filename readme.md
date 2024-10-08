# BUILD:

```shell
docker build -t pecherskiy/s3-proxy -f ./docker/prod/Dockerfile .
```

```shell
docker push pecherskiy/s3-proxy
```

```shell
docker buildx build --push --platform linux/arm64/v8,linux/amd64 -t pecherskiy/s3-proxy:1.0.0 .
```