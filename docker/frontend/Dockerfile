FROM node:20-alpine AS base

RUN npm install -g nuxi

WORKDIR /app

COPY apps/frontend/package*.json ./
RUN npm install

COPY apps/frontend/ ./

EXPOSE 3000

ENV NUXT_HOST=0.0.0.0
ENV NUXT_PORT=3000

CMD ["npm", "run", "dev"]
