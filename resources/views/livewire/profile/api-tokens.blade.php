<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public string $tokenName     = '';
    public ?string $newTokenValue = null;

    #[Computed]
    public function tokens()
    {
        return Auth::user()->tokens()->latest()->get();
    }

    public function createToken(): void
    {
        $this->validate([
            'tokenName' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $newToken           = Auth::user()->createToken($this->tokenName);
        $this->newTokenValue = $newToken->plainTextToken;
        $this->tokenName    = '';

        unset($this->tokens);
    }

    public function revokeToken(int $tokenId): void
    {
        $token = Auth::user()->tokens()->find($tokenId);

        if ($token) {
            $token->delete();
        }

        $this->newTokenValue = null;
        unset($this->tokens);
    }

    public function clearNewToken(): void
    {
        $this->newTokenValue = null;
    }
}
?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">API Tokens</h2>
        <p class="mt-1 text-sm text-gray-600">
            Generate personal access tokens to connect AI tools like Claude Desktop to your EasyTicket account.
        </p>
    </header>

    {{-- New token one-time display --}}
    @if ($newTokenValue)
        <div class="mt-4 p-4 bg-green-50 border border-green-300 rounded-lg">
            <p class="text-sm font-medium text-green-800 mb-2">
                Your new token (shown only once — copy it now):
            </p>
            <div class="flex items-center gap-2">
                <code class="flex-1 p-2 bg-white border border-green-200 rounded text-sm font-mono break-all text-gray-800">
                    {{ $newTokenValue }}
                </code>
            </div>
            <button
                wire:click="clearNewToken"
                class="mt-3 text-sm text-green-700 underline hover:text-green-900"
            >
                Done — I've copied my token
            </button>
        </div>
    @endif

    {{-- Generate new token form --}}
    <form wire:submit="createToken" class="mt-6 space-y-4">
        <div>
            <x-input-label for="tokenName" value="Token Name" />
            <div class="mt-1 flex gap-2">
                <x-text-input
                    id="tokenName"
                    wire:model="tokenName"
                    type="text"
                    class="flex-1"
                    placeholder="e.g. Claude Desktop"
                    autocomplete="off"
                />
                <x-primary-button type="submit">
                    Generate
                </x-primary-button>
            </div>
            <x-input-error :messages="$errors->get('tokenName')" class="mt-1" />
        </div>
    </form>

    {{-- Token list --}}
    @if ($this->tokens->isNotEmpty())
        <div class="mt-6">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Active Tokens</h3>
            <ul class="divide-y divide-gray-200 border border-gray-200 rounded-lg overflow-hidden">
                @foreach ($this->tokens as $token)
                    <li class="flex items-center justify-between px-4 py-3 bg-white">
                        <div>
                            <span class="text-sm font-medium text-gray-900">{{ $token->name }}</span>
                            <span class="ml-2 text-xs text-gray-500">
                                Created {{ $token->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <button
                            wire:click="revokeToken({{ $token->id }})"
                            wire:confirm="Revoke '{{ $token->name }}'? Any MCP client using this token will stop working."
                            class="text-sm text-red-600 hover:text-red-800 font-medium"
                        >
                            Revoke
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @else
        <p class="mt-6 text-sm text-gray-500">No active tokens. Generate one to connect MCP clients.</p>
    @endif
</section>
