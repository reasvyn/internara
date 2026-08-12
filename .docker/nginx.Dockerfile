FROM nginx:alpine

# Copy custom nginx config from repo into image
COPY ./.docker/nginx.conf /etc/nginx/conf.d/default.conf

# Ensure storage mountpoint exists and is writable by nginx user
RUN mkdir -p /app/storage && chown -R nginx:nginx /app/storage

VOLUME ["/app/storage"]
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
