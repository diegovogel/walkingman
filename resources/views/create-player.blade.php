<x-layouts.app.simple title="Create a player"
                      page-title="Create a player">

    @session('success')
    <div class="bg-emerald-100 rounded-sm p-4 pb-0.5">
        <p><strong>{{$value}}</strong></p>

        <p class="">You can now play games.</p>
    </div>
    @endsession

    <form method="POST"
          action="{{route('submit-player-form')}}">
        @csrf

        <label>
            Player name
            <input type="text"
                   name="player_name"
                   aria-describedby="player-name-helper-text"
                   value="{{old('player_name')}}"
                   @error('player_name') aria-invalid="true"
                   @enderror
                   placeholder="l33t_h4kkr"/>

            @error('player_name')
            <small id="player-name-helper-text">{{$message}}</small>
            @else
                <small id="player-name-helper-text">Letters, numbers, hyphens (-), and underscores (_) only.</small>
                @enderror
        </label>
    </form>
</x-layouts.app.simple>
