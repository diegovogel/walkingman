<x-layouts.app.simple title="Create a player"
                      page-title="Create a player">

    @session('success')
    <mark>{{$value}}</mark>
    @endsession

    @if ($errors->any())
        <div>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{route('submit-player-form')}}">
        @csrf

        <label>
            Player name
            <input type="text"
                   name="player_name"
                   aria-labelledby="player-name-description"
                   value="{{old('player_name')}}"
                   placeholder="l33t_h4kkr"/>

            <small id="player-name-description">Letters, numbers, hyphens (-), and underscores (_) only.</small>
        </label>
    </form>
</x-layouts.app.simple>
