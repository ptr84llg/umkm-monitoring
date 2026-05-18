<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class SafeErrorResponder
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (ValidationException $exception) {
            if (! $this->expectsSafeJson($request)) {
                throw $exception;
            }

            return response()->json([
                'ok' => false,
                'message' => 'Validasi belum terpenuhi.',
                'errors' => $exception->errors(),
            ], 422);
        } catch (AuthenticationException $exception) {
            if (! $this->expectsSafeJson($request)) {
                throw $exception;
            }

            return response()->json([
                'ok' => false,
                'message' => 'Sesi belum valid. Silakan masuk kembali.',
            ], 401);
        } catch (AuthorizationException $exception) {
            if (! $this->expectsSafeJson($request)) {
                throw $exception;
            }

            return response()->json([
                'ok' => false,
                'message' => 'Anda tidak memiliki izin untuk melakukan aksi ini.',
            ], 403);
        } catch (TokenMismatchException $exception) {
            if (! $this->expectsSafeJson($request)) {
                throw $exception;
            }

            return response()->json([
                'ok' => false,
                'message' => 'Sesi keamanan berakhir. Muat ulang halaman dan coba kembali.',
            ], 419);
        } catch (Throwable $exception) {
            if (! $this->expectsSafeJson($request)) {
                throw $exception;
            }

            if ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();

                return response()->json([
                    'ok' => false,
                    'message' => $this->safeMessageForStatus($status),
                ], $status, $exception->getHeaders());
            }

            report($exception);

            return response()->json([
                'ok' => false,
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    private function expectsSafeJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->ajax()
            || $request->header('X-UMKM-Request') === 'internal';
    }

    private function safeMessageForStatus(int $status): string
    {
        return match ($status) {
            400 => 'Permintaan tidak valid.',
            401 => 'Sesi belum valid. Silakan masuk kembali.',
            403 => 'Akses tidak diizinkan.',
            404 => 'Data atau halaman tidak ditemukan.',
            405 => 'Metode permintaan tidak diizinkan.',
            419 => 'Sesi keamanan berakhir. Muat ulang halaman dan coba kembali.',
            422 => 'Validasi belum terpenuhi.',
            429 => 'Terlalu banyak percobaan. Tunggu beberapa saat sebelum mencoba kembali.',
            default => $status >= 500
                ? 'Terjadi kesalahan pada sistem.'
                : 'Permintaan tidak dapat diproses.',
        };
    }
}
