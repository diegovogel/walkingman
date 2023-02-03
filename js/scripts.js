const speed = 100000, // mph
      $originNameCont = $('.previous-location .location-name'),
      $originDateCont = $('.previous-location .date-time'),
      $destinationNameCont = $('.next-location .location-name'),
      $destinationDateCont = $('.next-location .date-time'),
      $percentageCont = $('.progress .percentage');

// Get current trip data.
$.get('data/current.json', ( data ) => {
  const origin = data[0],
        destination = data[1];

  if (origin && destination) {
    const tripDist = geoDist(origin.lat, origin.lon, destination.lat, destination.lon),
          distTravelled = (Date.now() - Number(origin.leftAt)) / 3600000 * speed;

    if (distTravelled >= tripDist) {
      initTrip(destination);
    } else {
      let eta = destination.eta;
  
      if (!eta) {
        eta = (tripDist / speed) * 3600000 + origin.leftAt;
        setEta(eta);
      }

      updateTripDisplay(
        Math.round(distTravelled/tripDist * 100),
        data[1],
        data[0]
      );
    }
  } else {
    initTrip();
  }
  
});

// Calculate distance between current cities.
function geoDist(lat1, lon1, lat2, lon2) {
  const R = 3958.8; // miles
  const φ1 = lat1 * Math.PI/180; // φ, λ in radians
  const φ2 = lat2 * Math.PI/180;
  const Δφ = (lat2-lat1) * Math.PI/180;
  const Δλ = (lon2-lon1) * Math.PI/180;

  const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
            Math.cos(φ1) * Math.cos(φ2) *
            Math.sin(Δλ/2) * Math.sin(Δλ/2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

  const d = R * c; // in miles

  return d;
}

// Set ETA for destination.
function setEta(eta) {
  $.get(
    'api.php',
    {
      a: 'set_eta',
      eta: eta
    },
    () => {
      console.log('eta set');
    }
  );
}

// Random number generator.
function getRandomInt(min, max) {
  min = Math.ceil(min);
  max = Math.floor(max);
  return Math.floor(Math.random() * (max - min) + min); // The maximum is exclusive and the minimum is inclusive
}

// Initialize trip
function initTrip(o) {
  $.get('data/cities.json', (data) => {
    let origin = o ?? formattedLocation(data[getRandomInt(0, data.length)]),
        destination = formattedLocation(data[getRandomInt(0, data.length)]);

    // TODO: repeat until they're different instead of relying on extremely low odds.
    if (origin.name == destination.name) {
      destination = formattedLocation(data[getRandomInt(0, data.length)]);
    }

    const tripDist = geoDist(origin.lat, origin.lon, destination.lat, destination.lon),
          tripHours = tripDist / speed;

    origin.leftAt = Date.now();
    destination.eta = origin.leftAt + tripHours * 3600000;

    $.get(
      'api.php',
      {
        a: 'init',
        o: origin,
        d: destination
      },
      updateTripDisplay(0, destination, origin)
    );
  });
}

// Format location data the way we need it.
function formattedLocation(l) {
  const formattedLocationObj = {
    name: `${l.city}, ${l.state_id}`,
    lat: l.lat,
    lon: l.lng,
  }

  return formattedLocationObj;
}

// Update displayed trip info based on current data.
function updateTripDisplay(p, d, o) {
  if (o) {
    $originNameCont.text(o.name);
    $originDateCont.text(new Date(Number(o.leftAt)).toLocaleString());
  }
  
  if (d) {
    $destinationNameCont.text(d.name);
    $destinationDateCont.text(new Date(Number(d.eta)).toLocaleString());
  }

  if (p || p === 0) {
    $percentageCont.text(`He's ${p}% of the way there!`);
  }
}